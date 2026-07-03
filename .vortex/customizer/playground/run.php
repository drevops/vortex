#!/usr/bin/env php
<?php

/**
 * @file
 * Customizer - interactive TUI prototype (throwaway spike).
 *
 * A self-contained, panel-style "control panel". The reusable ENGINE (this
 * file) is separate from the project DATA (a YAML config, default
 * config/vortex.yml), so the TUI could ship as its own package and each
 * project supplies its own panels/fields/conditionals/sub-panels.
 *
 * Navigation is a recursive panel stack: the root panel lists the top-level
 * panels; entering a panel shows its fields and any nested sub-panels; entering
 * a sub-panel drills deeper; esc pops back up.
 *
 * It takes over the whole terminal (alternate screen buffer). Because that
 * disables the terminal's own scrollback, the panel scrolls internally: a
 * pinned header + footer with a scrollable body that follows the cursor and
 * shows ▲/▼ indicators. The mouse wheel scrolls the panel without moving the
 * cursor.
 *
 * Run interactively:
 *   php .vortex/customizer/playground/run.php
 *
 * Options:
 *   --config=PATH  Use a different YAML config (default config/vortex.yml).
 *   --demo         Print a static storyboard of key frames and exit.
 *   --no-color     Disable ANSI colour (useful for alignment inspection).
 *   --update       Simulate "update existing project" mode (shows `auto` badges).
 *
 * Keys: up/down move - pgup/pgdn/home/end jump - enter open/edit - esc back -
 *       a apply - q quit. Mouse wheel scrolls.
 */

declare(strict_types=1);

// -----------------------------------------------------------------------------
// Layout + colour primitives.
// -----------------------------------------------------------------------------

const COLS = 78;
const IND = '  ';

$GLOBALS['color'] = TRUE;

function paint(string $s, string $codes): string {
  if (!$GLOBALS['color'] || $s === '') {
    return $s;
  }
  // Re-open our style after any embedded reset so nested styles survive.
  $s = str_replace("\033[0m", "\033[0m\033[" . $codes . 'm', $s);
  return "\033[" . $codes . 'm' . $s . "\033[0m";
}

function cyan(string $s): string { return paint($s, '36'); }
function green(string $s): string { return paint($s, '32'); }
function yellow(string $s): string { return paint($s, '33'); }
function magenta(string $s): string { return paint($s, '35'); }
function blue(string $s): string { return paint($s, '34'); }
function red(string $s): string { return paint($s, '31'); }
function grey(string $s): string { return paint($s, '90'); }
function bold(string $s): string { return paint($s, '1'); }
function dim(string $s): string { return paint($s, '2'); }
function reverse(string $s): string { return paint($s, '7'); }
function boldcyan(string $s): string { return paint($s, '1;36'); }
function boldgreen(string $s): string { return paint($s, '1;32'); }

/**
 * Visible length - ignores ANSI SGR sequences and counts display columns.
 */
function vlen(string $s): int {
  $s = preg_replace('/\033\[[0-9;]*m/', '', $s);
  return mb_strwidth($s);
}

function pad_right(string $s, int $w): string {
  $len = vlen($s);
  return $len < $w ? $s . str_repeat(' ', $w - $len) : $s;
}

/**
 * Truncate a plain (un-coloured) string to a display width, adding an ellipsis.
 */
function clip(string $s, int $w): string {
  if (mb_strwidth($s) <= $w) {
    return $s;
  }
  $out = '';
  $used = 0;
  foreach (preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
    $cw = mb_strwidth($ch);
    if ($used + $cw > $w - 1) {
      break;
    }
    $out .= $ch;
    $used += $cw;
  }
  return $out . '…';
}

/**
 * A left segment and a right segment on one line, right-aligned to COLS.
 */
function spread(string $left, string $right, int $cols = COLS): string {
  $gap = $cols - vlen($left) - vlen($right);
  $gap = max(1, $gap);
  return $left . str_repeat(' ', $gap) . $right;
}

function rule(): string {
  return ' ' . dim(str_repeat('─', COLS - 1));
}

/**
 * A divider with a right-aligned indicator, e.g. "▲ 3 more" / "▼ 8 more".
 */
function scroll_rule(string $right): string {
  $w = COLS - 1;
  $tag = ' ' . $right . ' ';
  $dash = str_repeat('─', max(0, $w - mb_strwidth($tag)));
  return ' ' . dim($dash) . cyan($tag);
}

// -----------------------------------------------------------------------------
// Minimal YAML loader (block maps/lists, scalars, inline scalar flow lists).
//
// Enough to read the config schema without a dependency; a real package would
// swap this for symfony/yaml or ext-yaml.
// -----------------------------------------------------------------------------

function yaml_load(string $text): array {
  $lines = [];
  foreach (explode("\n", $text) as $raw) {
    $raw = rtrim($raw, "\r");
    $trimmed = ltrim($raw, ' ');
    if ($trimmed === '' || $trimmed[0] === '#') {
      continue;
    }
    $lines[] = ['indent' => strlen($raw) - strlen($trimmed), 'content' => $trimmed];
  }
  $pos = 0;
  return $lines ? yaml_node($lines, $pos) : [];
}

function yaml_node(array &$lines, int &$pos): mixed {
  $content = $lines[$pos]['content'];
  if ($content === '-' || str_starts_with($content, '- ')) {
    return yaml_seq($lines, $pos, $lines[$pos]['indent']);
  }
  return yaml_map($lines, $pos, $lines[$pos]['indent']);
}

function yaml_map(array &$lines, int &$pos, int $indent): array {
  $map = [];
  while ($pos < count($lines) && $lines[$pos]['indent'] === $indent) {
    $content = $lines[$pos]['content'];
    if (str_starts_with($content, '- ') || $content === '-') {
      break;
    }
    $cp = strpos($content, ':');
    if ($cp === FALSE) {
      break;
    }
    $key = trim(substr($content, 0, $cp));
    $rest = trim(substr($content, $cp + 1));
    if ($rest === '') {
      $pos++;
      $map[$key] = ($pos < count($lines) && $lines[$pos]['indent'] > $indent) ? yaml_node($lines, $pos) : NULL;
    }
    elseif ($rest[0] === '[') {
      $map[$key] = yaml_flow($rest);
      $pos++;
    }
    else {
      $map[$key] = yaml_scalar($rest);
      $pos++;
    }
  }
  return $map;
}

function yaml_seq(array &$lines, int &$pos, int $indent): array {
  $items = [];
  while ($pos < count($lines) && $lines[$pos]['indent'] === $indent) {
    $content = $lines[$pos]['content'];
    if ($content !== '-' && !str_starts_with($content, '- ')) {
      break;
    }
    $after = ltrim(substr($content, 1), ' ');
    if ($after === '') {
      $pos++;
      $items[] = ($pos < count($lines) && $lines[$pos]['indent'] > $indent) ? yaml_node($lines, $pos) : NULL;
    }
    elseif (preg_match('/^[A-Za-z0-9_]+:(\s|$)/', $after)) {
      // Map item: re-root this line as a map body indented past the dash.
      $lines[$pos] = ['indent' => $indent + 2, 'content' => $after];
      $items[] = yaml_map($lines, $pos, $indent + 2);
    }
    elseif ($after[0] === '[') {
      $items[] = yaml_flow($after);
      $pos++;
    }
    else {
      $items[] = yaml_scalar($after);
      $pos++;
    }
  }
  return $items;
}

function yaml_flow(string $s): array {
  $s = trim(trim($s), '[]');
  $s = trim($s);
  if ($s === '') {
    return [];
  }
  return array_map(fn($x) => yaml_scalar(trim($x)), explode(',', $s));
}

function yaml_scalar(string $s): mixed {
  $s = trim($s);
  if ($s === '') {
    return '';
  }
  $lower = strtolower($s);
  if ($lower === 'true') {
    return TRUE;
  }
  if ($lower === 'false') {
    return FALSE;
  }
  if ($lower === 'null' || $s === '~') {
    return NULL;
  }
  if ($s[0] === "'" && str_ends_with($s, "'")) {
    return str_replace("''", "'", substr($s, 1, -1));
  }
  if ($s[0] === '"' && str_ends_with($s, '"')) {
    return str_replace('\\"', '"', substr($s, 1, -1));
  }
  if (preg_match('/^-?\d+$/', $s)) {
    return (int) $s;
  }
  return $s;
}

/**
 * Normalize one panel (recursively) into the engine's internal shape.
 */
function normalize_panel(array $panel): array {
  $fields = [];
  foreach ($panel['fields'] ?? [] as $f) {
    $type = $f['type'] ?? 'text';
    $field = [
      'id' => $f['id'],
      'label' => $f['label'] ?? $f['id'],
      'desc' => $f['description'] ?? '',
      'type' => $type,
      'default' => $f['default'] ?? ($type === 'multiselect' ? [] : ($type === 'confirm' ? FALSE : '')),
    ];
    foreach (['required', 'machine', 'auto'] as $flag) {
      if (!empty($f[$flag])) {
        $field[$flag] = TRUE;
      }
    }
    if (isset($f['when'])) {
      $field['when'] = $f['when'];
    }
    if (isset($f['options'])) {
      $opts = [];
      foreach ($f['options'] as $opt) {
        $opts[$opt['value']] = [$opt['label'] ?? $opt['value'], $opt['description'] ?? ''];
      }
      $field['options'] = $opts;
    }
    $fields[] = $field;
  }

  $subs = [];
  foreach ($panel['panels'] ?? [] as $sp) {
    $subs[] = normalize_panel($sp);
  }

  return ['id' => $panel['id'], 'title' => $panel['title'] ?? $panel['id'], 'desc' => $panel['description'] ?? '', 'fields' => $fields, 'panels' => $subs];
}

/**
 * Load and normalize a config file into the engine's internal shape.
 *
 * @return array{title:string,subject:string,sections:array}
 */
function load_config(string $path): array {
  if (!is_file($path)) {
    fwrite(STDERR, "Config not found: $path\n");
    exit(1);
  }
  $data = yaml_load((string) file_get_contents($path));
  $sections = array_map('normalize_panel', $data['panels'] ?? []);
  return ['title' => $data['title'] ?? 'Customizer', 'subject' => $data['subject'] ?? '', 'sections' => $sections];
}

// -----------------------------------------------------------------------------
// The application.
// -----------------------------------------------------------------------------

class Customizer {

  protected string $title;
  protected string $subject;
  protected array $sections;
  protected array $answers = [];
  protected array $defaults = [];
  protected bool $update = FALSE;

  protected string $screen = 'panel';
  /** @var int[] Indices from root to the current panel ([] = root). */
  protected array $path = [];
  protected int $cursor = 0;
  /** @var int[] Saved cursor per ancestor, for restoring on pop. */
  protected array $cursorStack = [];

  /** @var array<string,mixed> Transient editor state. */
  protected array $editor = [];

  protected int $rows = 40;
  protected int $scroll = 0;
  protected string $scrollKey = '';
  protected bool $manualScroll = FALSE;

  protected $in;
  protected string $buf = '';
  protected int $bufPos = 0;
  protected bool $scripted = FALSE;
  protected string $sttyRestore = '';

  public function __construct(array $config, bool $update = FALSE) {
    $this->title = $config['title'];
    $this->subject = $config['subject'];
    $this->sections = $config['sections'];
    $this->update = $update;
    $this->initAnswers($this->sections);
  }

  protected function initAnswers(array $panels): void {
    foreach ($panels as $panel) {
      foreach ($panel['fields'] as $field) {
        $this->answers[$field['id']] = $field['default'];
        $this->defaults[$field['id']] = $field['default'];
      }
      if (!empty($panel['panels'])) {
        $this->initAnswers($panel['panels']);
      }
    }
  }

  // ---- Tree navigation helpers ----------------------------------------------

  protected function currentPanel(): array {
    if (empty($this->path)) {
      return ['title' => $this->title, 'desc' => '', 'fields' => [], 'panels' => $this->sections];
    }
    $panel = $this->sections[$this->path[0]];
    for ($i = 1; $i < count($this->path); $i++) {
      $panel = $panel['panels'][$this->path[$i]];
    }
    return $panel;
  }

  /**
   * Navigable items of a panel: active fields, then sub-panels.
   */
  protected function panelItems(array $panel): array {
    $items = [];
    foreach ($panel['fields'] as $field) {
      if ($this->isActive($field)) {
        $items[] = ['kind' => 'field', 'field' => $field];
      }
    }
    foreach ($panel['panels'] ?? [] as $i => $sp) {
      $items[] = ['kind' => 'panel', 'panel' => $sp, 'index' => $i];
    }
    return $items;
  }

  protected function breadcrumb(): string {
    if (empty($this->path)) {
      return $this->title;
    }
    $panel = $this->sections[$this->path[0]];
    $titles = [$panel['title']];
    for ($i = 1; $i < count($this->path); $i++) {
      $panel = $panel['panels'][$this->path[$i]];
      $titles[] = $panel['title'];
    }
    return implode(' › ', $titles);
  }

  protected function pushPanel(int $index): void {
    $this->cursorStack[] = $this->cursor;
    $this->path[] = $index;
    $this->cursor = 0;
  }

  protected function popPanel(): void {
    array_pop($this->path);
    $this->cursor = array_pop($this->cursorStack) ?? 0;
  }

  // ---- Field / value helpers ------------------------------------------------

  protected function field(string $id): ?array {
    return $this->findField($this->sections, $id);
  }

  protected function findField(array $panels, string $id): ?array {
    foreach ($panels as $panel) {
      foreach ($panel['fields'] as $field) {
        if ($field['id'] === $id) {
          return $field;
        }
      }
      if (!empty($panel['panels'])) {
        $found = $this->findField($panel['panels'], $id);
        if ($found) {
          return $found;
        }
      }
    }
    return NULL;
  }

  protected function isActive(array $field): bool {
    return empty($field['when']) || $this->matchCond($field['when']);
  }

  /**
   * Evaluate a structured condition against the current answers.
   */
  protected function matchCond(array $c): bool {
    if (isset($c['all'])) {
      foreach ($c['all'] as $x) {
        if (!$this->matchCond($x)) {
          return FALSE;
        }
      }
      return TRUE;
    }
    if (isset($c['any'])) {
      foreach ($c['any'] as $x) {
        if ($this->matchCond($x)) {
          return TRUE;
        }
      }
      return FALSE;
    }
    if (isset($c['not'])) {
      return !$this->matchCond($c['not']);
    }
    $f = $c['field'] ?? NULL;
    if ($f === NULL) {
      return TRUE;
    }
    $val = $this->answers[$f] ?? NULL;
    if (array_key_exists('eq', $c)) {
      return $val == $c['eq'];
    }
    if (array_key_exists('ne', $c)) {
      return $val != $c['ne'];
    }
    if (array_key_exists('in', $c)) {
      return in_array($val, (array) $c['in'], FALSE);
    }
    if (array_key_exists('contains', $c)) {
      return in_array($c['contains'], (array) $val, FALSE);
    }
    return TRUE;
  }

  protected function isEdited(array $field): bool {
    return $this->answers[$field['id']] != $this->defaults[$field['id']];
  }

  protected function isAuto(array $field): bool {
    return $this->update && !empty($field['auto']) && !$this->isEdited($field);
  }

  protected function optLabel(array $field, string $key): string {
    $opt = $field['options'][$key] ?? NULL;
    if (is_array($opt)) {
      return $opt[0];
    }
    return $opt ?? $key;
  }

  protected function optDesc(array $field, string $key): string {
    $opt = $field['options'][$key] ?? NULL;
    return is_array($opt) ? ($opt[1] ?? '') : '';
  }

  /**
   * A compact human display of a field's current value.
   */
  protected function display(array $field): string {
    $v = $this->answers[$field['id']];
    switch ($field['type']) {
      case 'text':
        return ($v === '' || $v === NULL) ? '—' : (string) $v;

      case 'confirm':
        return $v ? 'yes' : 'no';

      case 'select':
      case 'suggest':
        return $this->optLabel($field, (string) $v);

      case 'multiselect':
        $arr = array_values((array) $v);
        if (!$arr) {
          return 'none';
        }
        if (count($arr) <= 3) {
          return implode(', ', array_map(fn($k) => $this->optLabel($field, $k), $arr));
        }
        return count($arr) . ' selected';
    }
    return (string) $v;
  }

  /**
   * One-line summary of a panel's active field values.
   */
  protected function summary(array $panel): string {
    $parts = [];
    foreach ($panel['fields'] as $field) {
      if ($this->isActive($field)) {
        $parts[] = $this->display($field);
      }
      if (count($parts) >= 4) {
        break;
      }
    }
    if (!$parts && !empty($panel['panels'])) {
      return count($panel['panels']) . ' sub-panel' . (count($panel['panels']) === 1 ? '' : 's');
    }
    return implode(' · ', $parts);
  }

  protected function panelEdited(array $panel): bool {
    foreach ($panel['fields'] as $field) {
      if ($this->isActive($field) && $this->isEdited($field)) {
        return TRUE;
      }
    }
    foreach ($panel['panels'] ?? [] as $sp) {
      if ($this->panelEdited($sp)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  protected function panelAuto(array $panel): bool {
    foreach ($panel['fields'] as $field) {
      if ($this->isActive($field) && $this->isAuto($field)) {
        return TRUE;
      }
    }
    foreach ($panel['panels'] ?? [] as $sp) {
      if ($this->panelAuto($sp)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * A cursor + title line with an optional right-aligned badge (no trailing pad).
   */
  protected function titleRow(string $marker, string $title, string $badge): string {
    return $badge === '' ? IND . $marker . $title : IND . spread($marker . $title, $badge . ' ');
  }

  protected function conditionText(array $field): string {
    return 'appears when ' . $this->describeCond($field['when'] ?? []);
  }

  /**
   * Human-readable rendering of a structured condition (data-driven).
   */
  protected function describeCond(array $c): string {
    if (isset($c['all'])) {
      return implode(' and ', array_map(fn($x) => $this->describeCond($x), $c['all']));
    }
    if (isset($c['any'])) {
      return implode(' or ', array_map(fn($x) => $this->describeCond($x), $c['any']));
    }
    if (isset($c['not'])) {
      return 'not (' . $this->describeCond($c['not']) . ')';
    }
    $f = (string) ($c['field'] ?? '');
    $label = $this->fieldLabelOf($f);
    if (array_key_exists('eq', $c)) {
      return $label . ' = ' . $this->valueLabelOf($f, $c['eq']);
    }
    if (array_key_exists('ne', $c)) {
      return $label . ' ≠ ' . $this->valueLabelOf($f, $c['ne']);
    }
    if (array_key_exists('in', $c)) {
      return $label . ' in [' . implode(', ', array_map(fn($v) => $this->valueLabelOf($f, $v), (array) $c['in'])) . ']';
    }
    if (array_key_exists('contains', $c)) {
      return $label . ' includes ' . (string) $c['contains'];
    }
    return 'its dependency is met';
  }

  protected function fieldLabelOf(string $id): string {
    $f = $this->field($id);
    return $f['label'] ?? $id;
  }

  protected function valueLabelOf(string $fieldId, mixed $value): string {
    if (is_bool($value)) {
      return $value ? 'Yes' : 'No';
    }
    $f = $this->field($fieldId);
    if ($f && isset($f['options'][(string) $value])) {
      return $this->optLabel($f, (string) $value);
    }
    return (string) $value;
  }

  // ---- Frames ---------------------------------------------------------------
  //
  // Every screen returns a frame: a pinned `header`, a scrollable `body`, a
  // pinned `footer`, and a `focus` line index within the body to keep visible
  // (-1 = no cursor). The last header line and first footer line are always
  // dividers, so composeFrame() can stamp ▲/▼ onto them.

  protected function frame(): array {
    return match ($this->screen) {
      'editor' => $this->renderEditor(),
      'review' => $this->renderReview(),
      default => $this->renderPanel(),
    };
  }

  public function renderPanel(): array {
    $panel = $this->currentPanel();
    $root = empty($this->path);

    if ($root) {
      $header = [
        '',
        IND . spread(bold($this->title) . dim('  ·  configure ') . cyan('"' . $this->subject . '"'), dim('↑/↓  ·  ↵ open  ·  a apply  ·  q quit')),
        rule(),
      ];
    }
    else {
      $header = [
        '',
        IND . spread(bold($this->breadcrumb()), dim('esc back')),
        rule(),
      ];
    }

    $body = [];
    $focus = -1;
    $nav = 0;

    foreach ($panel['fields'] as $field) {
      if (!$this->isActive($field)) {
        $plain = '⌁ ' . $field['label'] . '   inactive · ' . $this->conditionText($field);
        $body[] = IND . '  ' . grey(clip($plain, COLS - 4));
        continue;
      }
      $sel = $this->screen === 'panel' && $this->cursor === $nav;
      if ($sel) {
        $focus = count($body);
      }
      $marker = $sel ? boldcyan('❯ ') : '  ';
      $label = $sel ? boldcyan($field['label']) : $field['label'];
      $badge = $this->isEdited($field) ? yellow('✎') : ($this->isAuto($field) ? dim('auto') : '');
      $body[] = $this->titleRow($marker, $label, $badge);
      $body[] = IND . '    ' . grey(clip($field['desc'], COLS - 6));
      $value = clip($this->display($field), COLS - 6);
      $body[] = IND . '    ' . ($sel ? cyan($value) : dim($value));
      $nav++;
    }

    foreach ($panel['panels'] ?? [] as $sp) {
      $sel = $this->screen === 'panel' && $this->cursor === $nav;
      if ($sel) {
        $focus = count($body);
      }
      $marker = $sel ? boldcyan('❯ ') : '  ';
      $title = ($sel ? boldcyan($sp['title']) : $sp['title']) . dim(' ›');
      $badge = $this->panelEdited($sp) ? yellow('✎') : ($this->panelAuto($sp) ? dim('auto') : '');
      $body[] = $this->titleRow($marker, $title, $badge);
      $body[] = IND . '    ' . grey(clip($sp['desc'], COLS - 6));
      $summary = clip($this->summary($sp), COLS - 6);
      $body[] = IND . '    ' . ($sel ? $summary : dim($summary));
      $nav++;
    }

    if ($root) {
      $selReview = $this->screen === 'panel' && $this->cursor === $nav;
      if ($selReview) {
        $focus = count($body) - 1;
      }
      $marker = $selReview ? boldcyan('❯ ') : '  ';
      $label = $selReview ? boldgreen('Review & apply') : green('Review & apply');
      $footer = [rule(), IND . spread($marker . boldgreen('✔') . ' ' . $label, dim('nothing written yet'))];
    }
    else {
      $footer = [rule(), IND . dim('↑/↓ move  ·  ↵ open/edit  ·  esc back  ·  r reset')];
    }

    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => $focus];
  }

  public function renderEditor(): array {
    $field = $this->editor['field'];
    return match ($field['type']) {
      'select' => $this->renderChoice($field, FALSE),
      'suggest' => $this->renderChoice($field, TRUE),
      'multiselect' => $this->renderMulti($field),
      'confirm' => $this->renderConfirm($field),
      default => $this->renderText($field),
    };
  }

  /**
   * Pinned editor header: blank, "A › B › Field" (+ optional right tag), rule.
   */
  protected function editorHeader(array $field, string $right = ''): array {
    $title = bold($this->breadcrumb()) . dim(' › ') . boldcyan($field['label']);
    return ['', $right === '' ? IND . $title : IND . spread($title, dim($right)), rule()];
  }

  protected function renderChoice(array $field, bool $suggest): array {
    $header = $this->editorHeader($field);
    $body = [IND . grey($field['desc']), ''];

    $keys = array_keys($field['options']);
    if ($suggest) {
      $filter = (string) $this->editor['filter'];
      $keys = array_values(array_filter($keys, fn($k) => $filter === '' || stripos($k, $filter) !== FALSE));
      $body[] = IND . dim('filter: ') . ($filter === '' ? dim('(type to filter)') : cyan($filter)) . cyan('▏');
      $body[] = '';
    }

    $focus = -1;
    if (!$keys) {
      $body[] = IND . '  ' . dim('no matches');
    }
    $cursor = min((int) $this->editor['cursor'], max(0, count($keys) - 1));
    foreach ($keys as $idx => $key) {
      $sel = $idx === $cursor;
      if ($sel) {
        $focus = count($body);
      }
      $on = $this->editor['value'] === $key;
      $radio = $on ? boldgreen('(•)') : dim('( )');
      $label = $sel ? boldcyan($this->optLabel($field, $key)) : $this->optLabel($field, $key);
      $desc = $this->optDesc($field, $key);
      $line = $desc === '' ? $radio . ' ' . $label : $radio . ' ' . pad_right($label, 20) . ' ' . grey($desc);
      $body[] = IND . ($sel ? boldcyan('❯ ') : '  ') . $line;
    }

    $hint = $suggest ? 'type filter  ·  ↑/↓ move  ·  ↵ select  ·  esc cancel' : '↑/↓ move  ·  ↵ select  ·  esc cancel';
    $footer = [rule(), IND . dim($hint)];
    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => $focus];
  }

  protected function renderMulti(array $field): array {
    $all = array_keys($field['options']);
    $filter = (string) $this->editor['filter'];
    $keys = array_values(array_filter($all, fn($k) => $filter === '' || stripos($k, $filter) !== FALSE));
    $selected = (array) $this->editor['value'];
    $count = count(array_intersect($all, $selected));

    $header = $this->editorHeader($field, $count . ' of ' . count($all) . ' selected');
    $body = [IND . grey($field['desc']), ''];
    if ($filter !== '') {
      $body[] = IND . dim('filter: ') . cyan($filter) . cyan('▏');
      $body[] = '';
    }

    $cursor = min((int) $this->editor['cursor'], max(0, count($keys) - 1));
    $focus = -1;
    foreach ($keys as $idx => $key) {
      $sel = $idx === $cursor;
      if ($sel) {
        $focus = count($body);
      }
      $on = in_array($key, $selected, TRUE);
      $box = $on ? boldgreen('[x]') : dim('[ ]');
      $label = $sel ? boldcyan($this->optLabel($field, $key)) : $this->optLabel($field, $key);
      $desc = $this->optDesc($field, $key);
      $line = $desc === '' ? $box . ' ' . $label : $box . ' ' . pad_right($label, 22) . ' ' . grey(clip($desc, COLS - 34));
      $body[] = IND . ($sel ? boldcyan('❯ ') : '  ') . $line;
    }

    $footer = [rule(), IND . dim('space toggle  ·  type to filter  ·  ↑/↓ move  ·  ↵ confirm  ·  esc cancel')];
    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => $focus];
  }

  protected function renderConfirm(array $field): array {
    $header = $this->editorHeader($field);
    $body = [IND . grey($field['desc']), ''];
    $focus = -1;
    foreach ([1 => 'Yes', 0 => 'No'] as $flag => $label) {
      $val = (bool) $flag;
      $sel = $this->editor['value'] === $val;
      if ($sel) {
        $focus = count($body);
      }
      $radio = $sel ? boldgreen('(•)') : dim('( )');
      $text = $sel ? boldcyan($label) : $label;
      $body[] = IND . ($sel ? boldcyan('❯ ') : '  ') . $radio . ' ' . $text;
    }
    $footer = [rule(), IND . dim('↑/↓ or y/n  ·  ↵ confirm  ·  esc cancel')];
    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => $focus];
  }

  protected function renderText(array $field): array {
    $header = $this->editorHeader($field);
    $text = (string) $this->editor['value'];
    $inner = COLS - 8;
    $body = [
      IND . grey($field['desc']),
      '',
      IND . '  ' . dim('┌' . str_repeat('─', $inner) . '┐'),
      IND . '  ' . dim('│') . ' ' . pad_right(cyan($text) . reverse(' '), $inner - 2) . ' ' . dim('│'),
      IND . '  ' . dim('└' . str_repeat('─', $inner) . '┘'),
      '',
    ];
    $err = $this->editor['error'] ?? '';
    $body[] = $err !== '' ? IND . '  ' . red('✕ ' . $err) : IND . '  ' . green('✔ Looks good.');
    $footer = [rule(), IND . dim('type to edit  ·  ↵ save  ·  esc cancel')];
    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => 0];
  }

  public function renderReview(): array {
    $header = ['', IND . bold('Review & apply'), rule()];
    $body = [];
    foreach ($this->sections as $panel) {
      $this->reviewPanel($panel, $body, 0);
    }
    $footer = [
      rule(),
      IND . dim('↑/↓ scroll  ·  ') . reverse(boldgreen(' ↵ apply ')) . dim('  ·  esc back  ·  q quit'),
    ];
    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => -1];
  }

  protected function reviewPanel(array $panel, array &$body, int $depth): void {
    $indent = str_repeat('  ', $depth);
    $body[] = IND . $indent . boldcyan($panel['title']);
    foreach ($panel['fields'] as $field) {
      if (!$this->isActive($field)) {
        continue;
      }
      $badge = $this->isEdited($field) ? '  ' . yellow('✎') : '';
      $body[] = IND . $indent . '  ' . pad_right($field['label'] . '  ', 32 - $depth * 2) . dim($this->display($field)) . $badge;
    }
    foreach ($panel['panels'] ?? [] as $sp) {
      $this->reviewPanel($sp, $body, $depth + 1);
    }
  }

  protected function frameToString(array $f): string {
    return implode("\n", array_merge($f['header'], $f['body'], $f['footer']));
  }

  // ---- Paint (viewport + scrolling) -----------------------------------------

  protected function paint(): void {
    $this->detectSize();
    echo "\033[2J\033[H" . $this->composeFrame();
  }

  protected function composeFrame(): string {
    $f = $this->frame();
    $key = $this->screen . ':' . implode('.', $this->path) . ':' . ($this->editor['field']['id'] ?? '');
    if ($key !== $this->scrollKey) {
      $this->scrollKey = $key;
      $this->scroll = 0;
      $this->manualScroll = FALSE;
    }

    $header = $f['header'];
    $footer = $f['footer'];
    $body = $f['body'];
    $avail = max(1, $this->rows - count($header) - count($footer));
    $total = count($body);

    $offset = $this->scroll;
    if ($f['focus'] >= 0 && !$this->manualScroll) {
      if ($f['focus'] < $offset) {
        $offset = $f['focus'];
      }
      if ($f['focus'] >= $offset + $avail) {
        $offset = $f['focus'] - $avail + 1;
      }
    }
    $offset = max(0, min($offset, max(0, $total - $avail)));
    $this->scroll = $offset;

    $view = array_slice($body, $offset, $avail);
    if ($total > $avail) {
      while (count($view) < $avail) {
        $view[] = '';
      }
      if ($offset > 0) {
        $header[count($header) - 1] = scroll_rule('▲ ' . $offset . ' more');
      }
      $below = $total - $offset - $avail;
      if ($below > 0) {
        $footer[0] = scroll_rule('▼ ' . $below . ' more');
      }
    }

    return implode("\n", array_merge($header, $view, $footer));
  }

  /**
   * Headless render of one screen at a fixed height (for verifying scrolling).
   */
  public function probe(int $rows, string $mode, int $index): void {
    $this->scripted = TRUE;
    $this->rows = $rows;
    if ($mode === 'review') {
      $this->screen = 'review';
    }
    elseif ($mode === 'section') {
      $this->screen = 'panel';
      $this->path = [min($index, count($this->sections) - 1)];
      $this->cursor = 0;
    }
    else {
      $this->screen = 'panel';
      $this->path = [];
      $this->cursor = $index;
    }
    echo $this->composeFrame() . "\n";
  }

  protected function detectSize(): void {
    if ($this->scripted) {
      return;
    }
    $out = trim((string) @shell_exec('stty size </dev/tty 2>/dev/null'));
    if (preg_match('/^(\d+)\s+(\d+)$/', $out, $m)) {
      $this->rows = max(8, (int) $m[1]);
    }
  }

  // ---- Input ----------------------------------------------------------------

  protected function key(): string {
    return $this->scripted ? $this->keyBuffered() : $this->keyTty();
  }

  protected function keyTty(): string {
    $c = fread($this->in, 1);
    if ($c === '' || $c === FALSE) {
      return 'EOF';
    }
    if (ord($c) === 0x1b) {
      stream_set_blocking($this->in, FALSE);
      $seq = '';
      for ($i = 0; $i < 32; $i++) {
        $n = fread($this->in, 1);
        if ($n === '' || $n === FALSE) {
          usleep(1200);
          $n = fread($this->in, 1);
        }
        if ($n === '' || $n === FALSE) {
          break;
        }
        $seq .= $n;
        if (ctype_alpha($n) || $n === '~') {
          break;
        }
      }
      stream_set_blocking($this->in, TRUE);
      return $this->csi($seq);
    }
    return $this->normalize(ord($c), $c);
  }

  protected function keyBuffered(): string {
    if ($this->bufPos >= strlen($this->buf)) {
      return 'EOF';
    }
    $c = $this->buf[$this->bufPos++];
    if (ord($c) === 0x1b) {
      $seq = '';
      while ($this->bufPos < strlen($this->buf) && strlen($seq) < 32) {
        $n = $this->buf[$this->bufPos++];
        $seq .= $n;
        if (ctype_alpha($n) || $n === '~') {
          break;
        }
      }
      return $this->csi($seq);
    }
    return $this->normalize(ord($c), $c);
  }

  /**
   * Map a CSI escape tail to a token. Empty tail = a lone ESC (back).
   */
  protected function csi(string $seq): string {
    if ($seq === '') {
      return 'ESC';
    }
    if ($seq[0] === '[' || $seq[0] === 'O') {
      $rest = substr($seq, 1);
      // SGR mouse report: <button;col;row(M|m). Wheel up = 64, wheel down = 65.
      if (($rest[0] ?? '') === '<') {
        if (preg_match('/^<(\d+);\d+;\d+[Mm]$/', $rest, $m)) {
          return match ((int) $m[1]) {
            64 => 'SCROLL_UP',
            65 => 'SCROLL_DOWN',
            default => 'NONE',
          };
        }
        return 'NONE';
      }
      return match ($rest) {
        'A' => 'UP', 'B' => 'DOWN', 'C' => 'RIGHT', 'D' => 'LEFT',
        '5~' => 'PGUP', '6~' => 'PGDN',
        'H', '1~', '7~' => 'HOME',
        'F', '4~', '8~' => 'END',
        default => 'ESC',
      };
    }
    return 'ESC';
  }

  protected function normalize(int $o, string $c): string {
    return match (TRUE) {
      $o === 3, $o === 4 => 'CTRL_C',
      $o === 13, $o === 10 => 'ENTER',
      $o === 127, $o === 8 => 'BACKSPACE',
      $o === 32 => 'SPACE',
      default => $c,
    };
  }

  // ---- Main loop ------------------------------------------------------------

  public function run(): int {
    $this->scripted = !@stream_isatty(STDIN);
    if ($this->scripted) {
      $this->buf = stream_get_contents(STDIN) ?: '';
      return $this->runScripted();
    }

    $this->in = STDIN;
    $this->sttyRestore = trim((string) shell_exec('stty -g 2>/dev/null'));
    shell_exec('stty -icanon -echo -isig min 1 time 0 2>/dev/null');
    register_shutdown_function([$this, 'restore']);
    // Alt screen + hide cursor + SGR mouse tracking (for wheel scrolling).
    echo "\033[?1049h\033[?25l\033[?1000h\033[?1006h";

    while (TRUE) {
      $this->paint();
      $k = $this->key();
      if ($k === 'CTRL_C' || $k === 'EOF') {
        break;
      }
      if ($this->dispatch($k) === FALSE) {
        break;
      }
    }
    $this->restore();
    echo "Bye.\n";
    return 0;
  }

  protected function runScripted(): int {
    while ($this->bufPos < strlen($this->buf)) {
      $k = $this->key();
      if ($k === 'CTRL_C' || $k === 'EOF') {
        break;
      }
      if ($this->dispatch($k) === FALSE) {
        break;
      }
    }
    $this->dumpAnswers();
    return 0;
  }

  /**
   * Headless driver for verification: comma-separated tokens, e.g.
   *   down,enter,type:Acme Digital,enter
   * Tokens: up down left right enter esc space back pgup pgdn home end
   * wheelup wheeldown, `type:<text>`, `render`, or a single char.
   */
  public function runKeys(string $spec): int {
    $map = ['up' => 'UP', 'down' => 'DOWN', 'left' => 'LEFT', 'right' => 'RIGHT', 'enter' => 'ENTER', 'esc' => 'ESC', 'space' => 'SPACE', 'back' => 'BACKSPACE', 'pgup' => 'PGUP', 'pgdn' => 'PGDN', 'home' => 'HOME', 'end' => 'END', 'wheelup' => 'SCROLL_UP', 'wheeldown' => 'SCROLL_DOWN'];
    // Mirror the first interactive paint priming the scroll key.
    $this->scrollKey = $this->screen . ':' . implode('.', $this->path) . ':' . ($this->editor['field']['id'] ?? '');
    foreach (explode(',', $spec) as $token) {
      if ($token === '' || $token === 'render') {
        continue;
      }
      if (str_starts_with($token, 'type:')) {
        foreach (preg_split('//u', substr($token, 5), -1, PREG_SPLIT_NO_EMPTY) as $ch) {
          $this->dispatch($ch === ' ' ? 'SPACE' : $ch);
        }
        continue;
      }
      if ($this->dispatch($map[$token] ?? $token) === FALSE) {
        break;
      }
    }
    if (str_contains(',' . $spec . ',', ',render,')) {
      $this->rows = 18;
      echo $this->composeFrame() . "\n";
      fwrite(STDOUT, sprintf("[debug] screen=%s path=%s cursor=%d scroll=%d manual=%d\n", $this->screen, implode('.', $this->path), $this->cursor, $this->scroll, $this->manualScroll ? 1 : 0));
    }
    else {
      $this->dumpAnswers();
    }
    return 0;
  }

  protected function dumpAnswers(): void {
    fwrite(STDOUT, "HEADLESS RUN - final answers:\n");
    foreach ($this->answers as $id => $v) {
      $v = is_array($v) ? implode(',', $v) : var_export($v, TRUE);
      $field = $this->field($id);
      $edited = ($field && $this->isEdited($field)) ? ' [edited]' : '';
      fwrite(STDOUT, sprintf("  %-28s %s%s\n", $id, $v, $edited));
    }
  }

  public function restore(): void {
    if ($this->scripted) {
      return;
    }
    echo "\033[?1000l\033[?1006l\033[?25h\033[?1049l";
    if ($this->sttyRestore !== '') {
      shell_exec('stty ' . $this->sttyRestore . ' 2>/dev/null');
    }
    else {
      shell_exec('stty sane 2>/dev/null');
    }
  }

  // ---- Dispatch -------------------------------------------------------------

  protected function dispatch(string $k): bool {
    if ($k === 'NONE') {
      return TRUE;
    }
    // Mouse wheel scrolls the viewport without moving the cursor.
    if ($k === 'SCROLL_UP') {
      $this->scroll = max(0, $this->scroll - 3);
      $this->manualScroll = TRUE;
      return TRUE;
    }
    if ($k === 'SCROLL_DOWN') {
      $this->scroll += 3;
      $this->manualScroll = TRUE;
      return TRUE;
    }
    // Any other key re-engages cursor-follow scrolling.
    $this->manualScroll = FALSE;
    return match ($this->screen) {
      'panel' => $this->onPanel($k),
      'editor' => $this->onEditor($k),
      'review' => $this->onReview($k),
      default => TRUE,
    };
  }

  protected function onPanel(string $k): bool {
    $panel = $this->currentPanel();
    $items = $this->panelItems($panel);
    $root = empty($this->path);
    $count = max(1, count($items) + ($root ? 1 : 0));
    switch ($k) {
      case 'UP':
        $this->cursor = ($this->cursor - 1 + $count) % $count;
        break;

      case 'DOWN':
        $this->cursor = ($this->cursor + 1) % $count;
        break;

      case 'PGUP':
      case 'HOME':
        $this->cursor = 0;
        break;

      case 'PGDN':
      case 'END':
        $this->cursor = $count - 1;
        break;

      case 'ESC':
      case 'LEFT':
        if (!$root) {
          $this->popPanel();
        }
        break;

      case 'ENTER':
      case 'RIGHT':
        if ($root && $this->cursor === $count - 1) {
          $this->screen = 'review';
          break;
        }
        $item = $items[$this->cursor] ?? NULL;
        if ($item === NULL) {
          break;
        }
        if ($item['kind'] === 'field') {
          $this->openEditor($item['field']);
        }
        else {
          $this->pushPanel($item['index']);
        }
        break;

      case 'a':
        if ($root) {
          $this->screen = 'review';
        }
        break;

      case 'r':
        foreach ($panel['fields'] as $field) {
          $this->answers[$field['id']] = $this->defaults[$field['id']];
        }
        break;

      case 'q':
        return FALSE;
    }
    return TRUE;
  }

  protected function openEditor(array $field): void {
    $this->screen = 'editor';
    $this->editor = [
      'field' => $field,
      'value' => $this->answers[$field['id']],
      'cursor' => 0,
      'filter' => '',
      'error' => '',
    ];
    if (in_array($field['type'], ['select', 'suggest'], TRUE)) {
      $keys = array_keys($field['options']);
      $pos = array_search($this->answers[$field['id']], $keys, TRUE);
      $this->editor['cursor'] = $pos === FALSE ? 0 : $pos;
    }
    if ($field['type'] === 'multiselect') {
      $this->editor['value'] = array_values((array) $this->answers[$field['id']]);
    }
  }

  protected function closeEditor(bool $save): void {
    if ($save) {
      $field = $this->editor['field'];
      $this->answers[$field['id']] = $this->editor['value'];
    }
    $this->screen = 'panel';
    // Re-clamp the cursor in case conditionals changed the active item set.
    $items = $this->panelItems($this->currentPanel());
    $max = count($items) + (empty($this->path) ? 1 : 0);
    $this->cursor = min($this->cursor, max(0, $max - 1));
  }

  protected function onEditor(string $k): bool {
    $field = $this->editor['field'];
    if ($k === 'CTRL_C') {
      return FALSE;
    }
    return match ($field['type']) {
      'select' => $this->onChoice($k, FALSE),
      'suggest' => $this->onChoice($k, TRUE),
      'multiselect' => $this->onMulti($k),
      'confirm' => $this->onConfirm($k),
      default => $this->onText($k),
    };
  }

  protected function visibleKeys(array $field, bool $filtered): array {
    $keys = array_keys($field['options']);
    if ($filtered) {
      $filter = (string) $this->editor['filter'];
      $keys = array_values(array_filter($keys, fn($k) => $filter === '' || stripos($k, $filter) !== FALSE));
    }
    return $keys;
  }

  protected function onChoice(string $k, bool $suggest): bool {
    $field = $this->editor['field'];
    $keys = $this->visibleKeys($field, $suggest);
    $n = max(1, count($keys));
    switch ($k) {
      case 'UP':
        $this->editor['cursor'] = ($this->editor['cursor'] - 1 + $n) % $n;
        break;

      case 'DOWN':
        $this->editor['cursor'] = ($this->editor['cursor'] + 1) % $n;
        break;

      case 'HOME':
      case 'PGUP':
        $this->editor['cursor'] = 0;
        break;

      case 'END':
      case 'PGDN':
        $this->editor['cursor'] = $n - 1;
        break;

      case 'ENTER':
        if ($keys) {
          $this->editor['value'] = $keys[$this->editor['cursor']] ?? $this->editor['value'];
          $this->closeEditor(TRUE);
        }
        break;

      case 'ESC':
        $this->closeEditor(FALSE);
        break;

      case 'BACKSPACE':
        if ($suggest) {
          $this->editor['filter'] = mb_substr((string) $this->editor['filter'], 0, -1);
          $this->editor['cursor'] = 0;
        }
        break;

      default:
        if ($suggest && strlen($k) === 1 && ctype_print($k)) {
          $this->editor['filter'] .= $k;
          $this->editor['cursor'] = 0;
        }
    }
    return TRUE;
  }

  protected function onMulti(string $k): bool {
    $field = $this->editor['field'];
    $keys = $this->visibleKeys($field, TRUE);
    $n = max(1, count($keys));
    $cursor = &$this->editor['cursor'];
    $cursor = min($cursor, $n - 1);
    switch ($k) {
      case 'UP':
        $cursor = ($cursor - 1 + $n) % $n;
        break;

      case 'DOWN':
        $cursor = ($cursor + 1) % $n;
        break;

      case 'HOME':
      case 'PGUP':
        $cursor = 0;
        break;

      case 'END':
      case 'PGDN':
        $cursor = $n - 1;
        break;

      case 'SPACE':
        if ($keys) {
          $selected_key = $keys[$cursor];
          $val = (array) $this->editor['value'];
          if (in_array($selected_key, $val, TRUE)) {
            $val = array_values(array_diff($val, [$selected_key]));
          }
          else {
            $val[] = $selected_key;
          }
          $this->editor['value'] = $val;
        }
        break;

      case 'ENTER':
        $this->closeEditor(TRUE);
        break;

      case 'ESC':
        $this->closeEditor(FALSE);
        break;

      case 'BACKSPACE':
        $this->editor['filter'] = mb_substr((string) $this->editor['filter'], 0, -1);
        $cursor = 0;
        break;

      default:
        if (strlen($k) === 1 && ctype_print($k) && $k !== ' ') {
          $this->editor['filter'] .= $k;
          $cursor = 0;
        }
    }
    return TRUE;
  }

  protected function onConfirm(string $k): bool {
    switch ($k) {
      case 'UP':
      case 'DOWN':
      case 'LEFT':
      case 'RIGHT':
        $this->editor['value'] = !$this->editor['value'];
        break;

      case 'y':
        $this->editor['value'] = TRUE;
        break;

      case 'n':
        $this->editor['value'] = FALSE;
        break;

      case 'ENTER':
        $this->closeEditor(TRUE);
        break;

      case 'ESC':
        $this->closeEditor(FALSE);
        break;
    }
    return TRUE;
  }

  protected function onText(string $k): bool {
    $field = $this->editor['field'];
    switch ($k) {
      case 'ENTER':
        $err = $this->validate($field, (string) $this->editor['value']);
        if ($err === '') {
          $this->closeEditor(TRUE);
        }
        else {
          $this->editor['error'] = $err;
        }
        break;

      case 'ESC':
        $this->closeEditor(FALSE);
        break;

      case 'BACKSPACE':
        $this->editor['value'] = mb_substr((string) $this->editor['value'], 0, -1);
        $this->editor['error'] = '';
        break;

      default:
        if ($k === 'SPACE') {
          $k = ' ';
        }
        if (strlen($k) === 1 && ctype_print($k)) {
          $this->editor['value'] .= $k;
          $this->editor['error'] = '';
        }
    }
    return TRUE;
  }

  protected function validate(array $field, string $value): string {
    if (!empty($field['required']) && trim($value) === '') {
      return $field['label'] . ' is required.';
    }
    if (!empty($field['machine']) && $value !== '' && !preg_match('/^[a-z][a-z0-9_]*$/', $value)) {
      return 'Use lowercase letters, numbers and underscores; must start with a letter.';
    }
    return '';
  }

  protected function onReview(string $k): bool {
    $page = max(1, $this->rows - 4);
    switch ($k) {
      case 'UP':
        $this->scroll = max(0, $this->scroll - 1);
        break;

      case 'DOWN':
        $this->scroll++;
        break;

      case 'PGUP':
        $this->scroll = max(0, $this->scroll - $page);
        break;

      case 'PGDN':
        $this->scroll += $page;
        break;

      case 'HOME':
        $this->scroll = 0;
        break;

      case 'END':
        $this->scroll = 100000;
        break;

      case 'ENTER':
        $this->paint();
        echo "\n" . IND . green('✔ (prototype) Apply is a stub - no files were changed.') . "\n";
        return FALSE;

      case 'ESC':
      case 'LEFT':
        $this->screen = 'panel';
        break;

      case 'q':
        return FALSE;
    }
    return TRUE;
  }

  // ---- Demo storyboard ------------------------------------------------------

  public function demo(): void {
    $frames = [];

    $this->screen = 'panel';
    $this->path = [];
    $this->cursor = 0;
    $frames['The control panel (hub)'] = $this->frameToString($this->renderPanel());

    $this->path = [1];
    $this->cursor = 1;
    $frames['A panel opened (Drupal)'] = $this->frameToString($this->renderPanel());

    // Workflow has direct fields plus a Migrations sub-panel.
    $this->path = [6];
    $this->cursor = 2;
    $frames['A panel with a sub-panel (Workflow)'] = $this->frameToString($this->renderPanel());

    $this->path = [6, 0];
    $this->cursor = 0;
    $frames['Inside a sub-panel (Migrations)'] = $this->frameToString($this->renderPanel());

    $this->screen = 'editor';
    $this->openEditorDemo('profile', 1);
    $frames['Select field (Profile)'] = $this->frameToString($this->renderEditor());

    $this->openEditorDemo('modules', 6);
    $frames['Multiselect field (Modules)'] = $this->frameToString($this->renderEditor());

    $this->openEditorDemo('name', 0);
    $frames['Text field (Site name)'] = $this->frameToString($this->renderEditor());

    $this->openEditorDemo('name', 0);
    $this->editor['value'] = '';
    $this->editor['error'] = 'Site name is required.';
    $frames['Text field with a validation error'] = $this->frameToString($this->renderEditor());

    $this->openEditorDemo('frontend_build', 0);
    $frames['Confirm field'] = $this->frameToString($this->renderConfirm($this->editor['field']));

    $this->screen = 'review';
    $frames['Review & apply'] = $this->frameToString($this->renderReview());

    foreach ($frames as $caption => $frame) {
      echo "\n" . IND . magenta('◆ ' . $caption) . "\n";
      echo $frame . "\n";
    }
  }

  protected function openEditorDemo(string $id, int $cursor): void {
    $this->path = [$this->sectionOf($id)];
    $this->cursor = 0;
    $field = $this->field($id);
    $this->editor = ['field' => $field, 'value' => $this->answers[$id], 'cursor' => $cursor, 'filter' => '', 'error' => ''];
    if ($field['type'] === 'multiselect') {
      $this->editor['value'] = array_values((array) $this->answers[$id]);
    }
  }

  protected function sectionOf(string $id): int {
    foreach ($this->sections as $i => $panel) {
      if ($this->panelHasField($panel, $id)) {
        return $i;
      }
    }
    return 0;
  }

  protected function panelHasField(array $panel, string $id): bool {
    foreach ($panel['fields'] as $field) {
      if ($field['id'] === $id) {
        return TRUE;
      }
    }
    foreach ($panel['panels'] ?? [] as $sp) {
      if ($this->panelHasField($sp, $id)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}

// -----------------------------------------------------------------------------
// Entry point.
// -----------------------------------------------------------------------------

$args = array_slice($argv, 1);
if (in_array('--no-color', $args, TRUE) || getenv('NO_COLOR')) {
  $GLOBALS['color'] = FALSE;
}

$config_path = __DIR__ . '/../config/vortex.yml';
foreach ($args as $a) {
  if (str_starts_with($a, '--config=')) {
    $config_path = substr($a, 9);
  }
}

$app = new Customizer(load_config($config_path), in_array('--update', $args, TRUE));

foreach ($args as $a) {
  if (str_starts_with($a, '--keys=')) {
    exit($app->runKeys(substr($a, 7)));
  }
  if (str_starts_with($a, '--probe=')) {
    [$r, $s, $i] = array_pad(explode(',', substr($a, 8)), 3, '');
    $app->probe((int) $r ?: 18, $s !== '' ? $s : 'hub', (int) $i);
    exit(0);
  }
}

if (in_array('--demo', $args, TRUE)) {
  $app->demo();
  exit(0);
}

exit($app->run());
