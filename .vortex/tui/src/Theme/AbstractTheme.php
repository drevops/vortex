<?php

declare(strict_types=1);

namespace DrevOps\Tui\Theme;

use DrevOps\Tui\Answers\Answers;
use DrevOps\Tui\Answers\Provenance;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Config\Panel;
use DrevOps\Tui\Render\Ansi;
use DrevOps\Tui\Render\Navigator;
use DrevOps\Tui\Render\Scroller;
use DrevOps\Tui\Render\Viewport;

/**
 * Abstract visual authority for the TUI - one self-contained class per theme.
 *
 * A theme owns the entire visual representation: the palette (per-role style
 * codes), the glyphs (marker, scroll indicators, separators), and how every
 * element is composed (field rows, sub-panel rows, descriptions, breadcrumb,
 * the scrolled frame and the start banner).
 *
 * To make a theme, subclass this and define its palette in defineStyles() and,
 * optionally, its glyphs in defineGlyphs(); override any render*
 * method for full control over layout. Register the class under a name with
 * {@see register()} so the config can select it - the config only ever
 * references a theme name.
 *
 * @package DrevOps\Tui\Theme
 */
abstract class AbstractTheme implements ThemeInterface {

  /**
   * The role => style-code map, resolved once from defineStyles().
   *
   * @var array<string,string>
   */
  protected array $styles;

  /**
   * The name => [unicode, ascii] glyph pair map, from defineGlyphs().
   *
   * @var array<string,array{0:string,1:string}>
   */
  protected array $glyphs;

  /**
   * The name => theme-class registry.
   *
   * @var array<string,class-string<\DrevOps\Tui\Theme\AbstractTheme>>
   */
  protected static array $registry = [
    'dark' => DarkTheme::class,
    'light' => LightTheme::class,
  ];

  /**
   * Construct a theme.
   *
   * @param bool $color
   *   Whether colour (ANSI) is enabled.
   * @param int $width
   *   The frame width used for right-aligned badges.
   * @param bool $unicode
   *   Whether Unicode glyphs are used; FALSE falls back to ASCII glyphs.
   */
  public function __construct(protected bool $color = TRUE, protected int $width = 76, protected bool $unicode = TRUE) {
    $this->styles = $this->defineStyles();
    $this->glyphs = $this->defineGlyphs();
  }

  /**
   * The role => style-code palette for this theme.
   *
   * @return array<string,string>
   *   The palette, keyed by role.
   */
  abstract protected function defineStyles(): array;

  /**
   * The name => [unicode, ascii] glyph pair map for this theme.
   *
   * Every glyph is defined here as a pair - the Unicode form and its ASCII
   * fallback - so a theme is a complete, self-contained definition. Clone a
   * concrete theme and override what you want - nothing comes from a base.
   *
   * @return array<string,array{0:string,1:string}>
   *   The glyphs, keyed by name, each a [unicode, ascii] pair.
   */
  abstract protected function defineGlyphs(): array;

  /**
   * Register a theme class under a name so a config can select it.
   *
   * @param string $name
   *   The theme name.
   * @param class-string<\DrevOps\Tui\Theme\AbstractTheme> $class
   *   The theme class.
   */
  public static function register(string $name, string $class): void {
    static::$registry[$name] = $class;
  }

  /**
   * Create a theme by name.
   *
   * Lowest friction first: a fully-qualified theme class name is instantiated
   * directly, so a config can point at a consumer's own theme class with no
   * registration. Otherwise the name is looked up in the registry ("dark",
   * "light", "default", or a name passed to {@see register()}), falling back
   * to dark.
   *
   * @param string $name
   *   A theme class name, a registered name, or "default" for dark.
   * @param bool $color
   *   Whether colour is enabled.
   * @param int $width
   *   The frame width.
   * @param bool $unicode
   *   Whether Unicode glyphs are used (FALSE falls back to ASCII).
   *
   * @return \DrevOps\Tui\Theme\AbstractTheme
   *   The theme instance.
   */
  public static function create(string $name = 'dark', bool $color = TRUE, int $width = 76, bool $unicode = TRUE): AbstractTheme {
    $name = $name === 'default' ? 'dark' : $name;

    $class = static::$registry[$name] ?? (is_subclass_of($name, self::class) ? $name : DarkTheme::class);

    return new $class($color, $width, $unicode);
  }

  /**
   * Detect whether the environment advertises a Unicode-capable locale.
   *
   * Mirrors prompty: the first set of LC_ALL, LC_CTYPE or LANG decides, and a
   * "UTF" locale enables Unicode. An unset locale falls back to ASCII.
   *
   * @return bool
   *   TRUE when a UTF locale is advertised.
   */
  public static function detectUnicode(): bool {
    foreach (['LC_ALL', 'LC_CTYPE', 'LANG'] as $var) {
      $value = getenv($var);
      if (is_string($value) && $value !== '') {
        return stripos($value, 'utf') !== FALSE;
      }
    }

    return FALSE;
  }

  /**
   * Detect whether the environment supports ANSI colour.
   *
   * Honours the NO_COLOR convention and the "dumb" terminal.
   *
   * @return bool
   *   TRUE unless NO_COLOR is set or TERM is "dumb".
   */
  public static function detectColor(): bool {
    if (getenv('NO_COLOR') !== FALSE) {
      return FALSE;
    }

    return getenv('TERM') !== 'dumb';
  }

  /**
   * Style text for a role.
   *
   * @param string $role
   *   The role name.
   * @param string $text
   *   The text.
   *
   * @return string
   *   The styled text (plain when colour is disabled).
   */
  public function style(string $role, string $text): string {
    return Ansi::style($text, $this->styleCodes($role));
  }

  /**
   * The raw ANSI style codes for a role.
   *
   * The numbers that go inside an escape sequence to colour or emphasise text -
   * for example "1;36" (bold cyan) for the "title" role. Prefer style(), which
   * wraps text in these; this returns the raw codes for callers that need them.
   *
   * @param string $role
   *   The role name (e.g. "title", "value", "description").
   *
   * @return string
   *   The ANSI codes (empty when colour is off or the role is unknown).
   */
  public function styleCodes(string $role): string {
    return $this->color ? ($this->styles[$role] ?? '') : '';
  }

  /**
   * Style text for a role, emphasised (bold) when its item is selected.
   *
   * Keeps the role's colour but makes the whole selected item bold, dropping
   * any existing bold or faint so the emphasis is clean.
   *
   * @param string $role
   *   The role name.
   * @param string $text
   *   The text.
   * @param bool $selected
   *   Whether the item is the selected (cursor) one.
   *
   * @return string
   *   The styled text, made bold when selected.
   */
  protected function styleSelected(string $role, string $text, bool $selected): string {
    $codes = $this->styleCodes($role);

    if ($selected && $this->color) {
      $drop = ['', '1', '2'];
      $parts = array_values(array_filter(explode(';', $codes), static fn(string $part): bool => !in_array($part, $drop, TRUE)));
      array_unshift($parts, '1');
      $codes = implode(';', $parts);
    }

    return Ansi::style($text, $codes);
  }

  /**
   * The glyph for a decorative element.
   *
   * @param string $name
   *   The glyph name (e.g. "marker", "indicator_up", "separator").
   *
   * @return string
   *   The glyph character (empty when unknown).
   */
  public function glyph(string $name): string {
    return $this->glyphs[$name][$this->unicode ? 0 : 1] ?? '';
  }

  /**
   * Whether colour is enabled.
   *
   * @return bool
   *   TRUE when colour is enabled.
   */
  public function hasColor(): bool {
    return $this->color;
  }

  /**
   * Whether Unicode glyphs are enabled.
   *
   * @return bool
   *   TRUE when Unicode glyphs are used, FALSE for the ASCII fallback.
   */
  public function hasUnicode(): bool {
    return $this->unicode;
  }

  /**
   * The number of navigable items in a panel (fields + sub-panels).
   *
   * @param \DrevOps\Tui\Config\Panel $panel
   *   The panel.
   *
   * @return int
   *   The item count.
   */
  public function itemCount(Panel $panel): int {
    return count($panel->fields) + count($panel->panels);
  }

  /**
   * Build the body lines and the line index of the selected item.
   *
   * @param \DrevOps\Tui\Config\Panel $panel
   *   The panel.
   * @param \DrevOps\Tui\Answers\Answers $answers
   *   The current answers.
   * @param int $cursor
   *   The selected item index.
   *
   * @return array{list<string>,int}
   *   The body lines and the selected item's first line index.
   */
  public function renderBody(Panel $panel, Answers $answers, int $cursor): array {
    $lines = [];
    $cursor_line = 0;
    $index = 0;

    foreach ($panel->fields as $field) {
      if ($index === $cursor) {
        $cursor_line = count($lines);
      }

      $lines[] = $this->renderFieldLine($field, $answers, $index === $cursor);
      if ($field->description !== '') {
        $lines[] = $this->renderDescriptionLine($field->description, $index === $cursor);
      }

      $index++;
    }

    foreach ($panel->panels as $subpanel) {
      if ($index === $cursor) {
        $cursor_line = count($lines);
      }

      $lines[] = $this->renderPanelLine($subpanel, $index === $cursor);
      if ($subpanel->description !== '') {
        $lines[] = $this->renderDescriptionLine($subpanel->description, $index === $cursor);
      }

      $summary = $this->summarizePanel($subpanel, $answers);
      if ($summary !== '') {
        $lines[] = $this->renderSummaryLine($summary, $index === $cursor);
      }

      $index++;
    }

    return [$lines, $cursor_line];
  }

  /**
   * Render a field row.
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field.
   * @param \DrevOps\Tui\Answers\Answers $answers
   *   The current answers.
   * @param bool $selected
   *   Whether the row is selected.
   *
   * @return string
   *   The row.
   */
  public function renderFieldLine(Field $field, Answers $answers, bool $selected): string {
    $left = $this->marker($selected) . ' ' . $this->styleSelected('label', $field->label, $selected) . '  ' . $this->styleSelected('value', $this->renderFieldValue($field, $answers->value($field->id)), $selected);

    $provenance = $answers->provenanceOf($field->id);
    if ($provenance === Provenance::Default) {
      return $left;
    }

    return Ansi::alignRight($left, $this->styleSelected('badge', ' ' . $provenance->value . ' ', $selected), $this->width);
  }

  /**
   * Render a sub-panel row.
   *
   * @param \DrevOps\Tui\Config\Panel $panel
   *   The sub-panel.
   * @param bool $selected
   *   Whether the row is selected.
   *
   * @return string
   *   The row.
   */
  public function renderPanelLine(Panel $panel, bool $selected): string {
    return $this->marker($selected) . ' ' . $this->styleSelected('label', $panel->title, $selected) . ' ' . $this->styleSelected('description', $this->glyph('arrow'), $selected);
  }

  /**
   * Render a description row.
   *
   * @param string $description
   *   The description.
   * @param bool $selected
   *   Whether the row's item is selected.
   *
   * @return string
   *   The row.
   */
  public function renderDescriptionLine(string $description, bool $selected): string {
    return '    ' . $this->styleSelected('description', $description, $selected);
  }

  /**
   * Summarize a sub-panel's active field values into one line, for the hub.
   *
   * Lets the hub show what is configured in each panel without drilling in.
   *
   * @param \DrevOps\Tui\Config\Panel $panel
   *   The sub-panel.
   * @param \DrevOps\Tui\Answers\Answers $answers
   *   The current answers.
   *
   * @return string
   *   The summary, or an empty string when the panel has no active fields.
   */
  public function summarizePanel(Panel $panel, Answers $answers): string {
    $parts = [];

    foreach ($panel->fields as $field) {
      if (!$answers->has($field->id)) {
        continue;
      }

      $value = $answers->value($field->id);
      $parts[] = is_array($value) && count($value) > 3 ? count($value) . ' selected' : $this->renderFieldValue($field, $value);

      if (count($parts) >= 4) {
        break;
      }
    }

    return implode(' ' . $this->glyph('dot') . ' ', $parts);
  }

  /**
   * Render a sub-panel value-summary row.
   *
   * @param string $summary
   *   The summary text.
   * @param bool $selected
   *   Whether the row's item is selected.
   *
   * @return string
   *   The row.
   */
  public function renderSummaryLine(string $summary, bool $selected): string {
    $max = max(1, $this->width - 4);
    $clipped = mb_strlen($summary) > $max ? mb_substr($summary, 0, $max - 1) . '…' : $summary;

    return '    ' . $this->styleSelected('value', $clipped, $selected);
  }

  /**
   * Render a breadcrumb line for the navigator.
   *
   * @param \DrevOps\Tui\Render\Navigator $navigator
   *   The navigator.
   *
   * @return string
   *   The breadcrumb line.
   */
  public function renderBreadcrumbLine(Navigator $navigator): string {
    return $this->style('breadcrumb', implode(' ' . $this->glyph('separator') . ' ', $navigator->breadcrumb()));
  }

  /**
   * Compose a frame: pinned header, scrolled body with indicators, footer.
   *
   * @param list<string> $header
   *   The pinned header lines.
   * @param list<string> $body
   *   The full body lines.
   * @param list<string> $footer
   *   The pinned footer lines.
   * @param \DrevOps\Tui\Render\Viewport $viewport
   *   The computed viewport.
   * @param int $height
   *   The body viewport height.
   *
   * @return string
   *   The composed frame.
   */
  public function renderFrame(array $header, array $body, array $footer, Viewport $viewport, int $height): string {
    $visible = (new Scroller())->slice($body, $viewport->offset, $height);

    $lines = $header;
    if ($viewport->has_above) {
      $lines[] = $this->style('indicator', '  ' . $this->glyph('indicator_up'));
    }

    $lines = array_merge($lines, $visible);

    if ($viewport->has_below) {
      $lines[] = $this->style('indicator', '  ' . $this->glyph('indicator_down'));
    }

    return implode("\n", array_merge($lines, $footer));
  }

  /**
   * Compose a start banner: the logo above an optional version line.
   *
   * @param string $logo
   *   The banner logo (may be multi-line).
   * @param string $version
   *   The version string, shown dimmed below the logo when non-empty.
   *
   * @return string
   *   The composed banner.
   */
  public function renderBanner(string $logo, string $version): string {
    $lines = [];

    foreach (explode("\n", $logo) as $line) {
      $lines[] = $this->style('title', $line);
    }

    if ($version !== '') {
      $lines[] = '';
      $lines[] = $this->style('footer', 'Version: ' . $version);
    }

    return implode("\n", $lines);
  }

  /**
   * Render the status line shown at the foot of a panel.
   *
   * @return string
   *   The themed status line (hint text and arrow glyphs).
   */
  public function renderStatusLine(): string {
    $dot = ' ' . $this->glyph('dot') . ' ';
    $hint = $this->glyph('arrow_up') . '/' . $this->glyph('arrow_down') . ' move' . $dot . $this->glyph('enter') . ' select' . $dot . 'esc back';

    return $this->style('footer', $hint);
  }

  /**
   * Render a row of inline submit/cancel buttons.
   *
   * @param list<string> $labels
   *   The button labels, in order.
   * @param int $selected
   *   The index of the selected button, or -1 for none.
   *
   * @return string
   *   The themed button row with the buttons side by side.
   */
  public function renderButtonBar(array $labels, int $selected): string {
    $parts = [];

    foreach ($labels as $index => $label) {
      $text = '[ ' . $label . ' ]';
      $parts[] = $index === $selected ? $this->style('cursor', $text) : $this->style('value', $text);
    }

    return '  ' . implode('  ', $parts);
  }

  /**
   * The selection marker.
   *
   * @param bool $selected
   *   Whether selected.
   *
   * @return string
   *   The marker.
   */
  protected function marker(bool $selected): string {
    return $selected ? $this->style('marker', $this->glyph('marker')) : ' ';
  }

  /**
   * Render a field's value readably, masking secret values.
   *
   * Password values render as a fixed-length mask so neither the value nor
   * its length shows on screen.
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field the value belongs to.
   * @param mixed $value
   *   The value.
   *
   * @return string
   *   The rendered value.
   */
  protected function renderFieldValue(Field $field, mixed $value): string {
    if ($field->type === FieldType::Password) {
      return is_string($value) && $value !== '' ? str_repeat($this->glyph('mask'), 8) : '';
    }

    return $this->renderValue($value);
  }

  /**
   * Render a value readably.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string
   *   The rendered value.
   */
  protected function renderValue(mixed $value): string {
    if (is_bool($value)) {
      return $value ? 'yes' : 'no';
    }

    if (is_array($value)) {
      return implode(', ', array_map(static fn(mixed $item): string => is_scalar($item) ? (string) $item : '', $value));
    }

    return is_scalar($value) ? (string) $value : '';
  }

}
