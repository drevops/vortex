<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tui;

use DrevOps\Customizer\Answers\Answers;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\Panel;

/**
 * Abstract visual authority for the TUI - one self-contained class per theme.
 *
 * A theme owns the entire visual representation: the palette (per-role SGR
 * styles), the glyphs (marker, scroll indicators, separators), and how every
 * element is composed (field rows, sub-panel rows, descriptions, breadcrumb,
 * the scrolled frame and the start banner).
 *
 * To make a theme, subclass this and define its palette in defineStyles() and,
 * optionally, its glyphs in defineGlyphs(); override any render*
 * method for full control over layout. Register the class under a name with
 * {@see register()} so the config can select it - the config only ever
 * references a theme name.
 *
 * @package DrevOps\Customizer\Tui
 */
abstract class Theme {

  /**
   * The role => SGR style map, resolved once from defineStyles().
   *
   * @var array<string,string>
   */
  protected array $styles;

  /**
   * The name => glyph map, resolved once from defineGlyphs().
   *
   * @var array<string,string>
   */
  protected array $glyphs;

  /**
   * The name => theme-class registry.
   *
   * @var array<string,class-string<\DrevOps\Customizer\Tui\Theme>>
   */
  protected static array $registry = [
    'dark' => DarkTheme::class,
    'light' => LightTheme::class,
  ];

  /**
   * Construct a theme.
   *
   * @param bool $color
   *   Whether colour is enabled.
   * @param int $width
   *   The frame width used for right-aligned badges.
   */
  public function __construct(protected bool $color = TRUE, protected int $width = 76) {
    $this->styles = $this->defineStyles();
    $this->glyphs = $this->defineGlyphs();
  }

  /**
   * The role => SGR palette for this theme.
   *
   * @return array<string,string>
   *   The palette, keyed by role.
   */
  abstract protected function defineStyles(): array;

  /**
   * The name => glyph map for this theme (override to change the glyphs).
   *
   * @return array<string,string>
   *   The glyphs, keyed by name.
   */
  protected function defineGlyphs(): array {
    return [
      'marker' => '❯',
      'indicator_up' => '▲',
      'indicator_down' => '▼',
      'separator' => '›',
      'arrow' => '›',
      'arrow_up' => '↑',
      'arrow_down' => '↓',
      'enter' => '↵',
      'dot' => '·',
    ];
  }

  /**
   * Register a theme class under a name so a config can select it.
   *
   * @param string $name
   *   The theme name.
   * @param class-string<\DrevOps\Customizer\Tui\Theme> $class
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
   *
   * @return \DrevOps\Customizer\Tui\Theme
   *   The theme instance.
   */
  public static function create(string $name = 'dark', bool $color = TRUE, int $width = 76): Theme {
    $name = $name === 'default' ? 'dark' : $name;

    $class = static::$registry[$name] ?? (is_subclass_of($name, self::class) ? $name : DarkTheme::class);

    return new $class($color, $width);
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
    return Ansi::style($text, $this->sgr($role));
  }

  /**
   * The SGR parameters for a role.
   *
   * @param string $role
   *   The role name.
   *
   * @return string
   *   The SGR parameters (empty when colour is disabled or unknown).
   */
  public function sgr(string $role): string {
    return $this->color ? ($this->styles[$role] ?? '') : '';
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
    return $this->glyphs[$name] ?? '';
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
   * The number of navigable items in a panel (fields + sub-panels).
   *
   * @param \DrevOps\Customizer\Config\Panel $panel
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
   * @param \DrevOps\Customizer\Config\Panel $panel
   *   The panel.
   * @param \DrevOps\Customizer\Answers\Answers $answers
   *   The current answers.
   * @param int $cursor
   *   The selected item index.
   *
   * @return array{list<string>,int}
   *   The body lines and the selected item's first line index.
   */
  public function body(Panel $panel, Answers $answers, int $cursor): array {
    $lines = [];
    $cursor_line = 0;
    $index = 0;

    foreach ($panel->fields as $field) {
      if ($index === $cursor) {
        $cursor_line = count($lines);
      }

      $lines[] = $this->fieldLine($field, $answers, $index === $cursor);
      if ($field->description !== '') {
        $lines[] = $this->descriptionLine($field->description);
      }

      $index++;
    }

    foreach ($panel->panels as $subpanel) {
      if ($index === $cursor) {
        $cursor_line = count($lines);
      }

      $lines[] = $this->panelLine($subpanel, $index === $cursor);
      if ($subpanel->description !== '') {
        $lines[] = $this->descriptionLine($subpanel->description);
      }

      $index++;
    }

    return [$lines, $cursor_line];
  }

  /**
   * Render a field row.
   *
   * @param \DrevOps\Customizer\Config\Field $field
   *   The field.
   * @param \DrevOps\Customizer\Answers\Answers $answers
   *   The current answers.
   * @param bool $selected
   *   Whether the row is selected.
   *
   * @return string
   *   The row.
   */
  public function fieldLine(Field $field, Answers $answers, bool $selected): string {
    $left = $this->marker($selected) . ' ' . $this->style('label', $field->label) . '  ' . $this->style('value', $this->renderValue($answers->value($field->id)));

    $provenance = $answers->provenanceOf($field->id);
    if ($provenance === 'default') {
      return $left;
    }

    return Ansi::alignRight($left, $this->style('badge', ' ' . $provenance . ' '), $this->width);
  }

  /**
   * Render a sub-panel row.
   *
   * @param \DrevOps\Customizer\Config\Panel $panel
   *   The sub-panel.
   * @param bool $selected
   *   Whether the row is selected.
   *
   * @return string
   *   The row.
   */
  public function panelLine(Panel $panel, bool $selected): string {
    return $this->marker($selected) . ' ' . $this->style('label', $panel->title) . ' ' . $this->style('description', $this->glyph('arrow'));
  }

  /**
   * Render a description row.
   *
   * @param string $description
   *   The description.
   *
   * @return string
   *   The row.
   */
  public function descriptionLine(string $description): string {
    return '    ' . $this->style('description', $description);
  }

  /**
   * Render a breadcrumb line for the navigator.
   *
   * @param \DrevOps\Customizer\Tui\Navigator $navigator
   *   The navigator.
   *
   * @return string
   *   The breadcrumb line.
   */
  public function breadcrumbLine(Navigator $navigator): string {
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
   * @param \DrevOps\Customizer\Tui\Viewport $viewport
   *   The computed viewport.
   * @param int $height
   *   The body viewport height.
   *
   * @return string
   *   The composed frame.
   */
  public function frame(array $header, array $body, array $footer, Viewport $viewport, int $height): string {
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
  public function banner(string $logo, string $version): string {
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
  public function statusLine(): string {
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
  public function buttonBar(array $labels, int $selected): string {
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
