<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tui;

use DrevOps\Customizer\Answers\Answers;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\Panel;

/**
 * The single visual authority for the TUI.
 *
 * A theme owns the entire visual representation: the palette (per-role SGR
 * styles), the glyphs (marker, scroll indicators, separators), and how every
 * element is composed (field rows, sub-panel rows, descriptions, breadcrumb,
 * the scrolled frame and the start banner). The config only references a theme
 * by name.
 *
 * Two ways to make a theme:
 * - Fast, data-only: call {@see Theme::register()} with a palette and/or glyph
 *   map, then select it by name. Omitted tokens fall back to the dark theme.
 * - Full control: subclass and override any render* method to change layout,
 *   spacing or composition - not just colours and glyphs.
 *
 * @package DrevOps\Customizer\Tui
 */
class Theme {

  /**
   * The role => SGR style map.
   *
   * @var array<string,string>
   */
  protected array $styles;

  /**
   * The name => glyph map.
   *
   * @var array<string,string>
   */
  protected array $glyphs;

  /**
   * Consumer-registered custom presets, keyed by name.
   *
   * @var array<string,array{styles?:array<string,string>,glyphs?:array<string,string>}>
   */
  protected static array $custom = [];

  /**
   * Register a custom theme preset that consumers can select by name.
   *
   * @param string $name
   *   The preset name.
   * @param array{styles?:array<string,string>,glyphs?:array<string,string>} $preset
   *   The preset with optional 'styles' (role => SGR) and 'glyphs' (name =>
   *   character) maps. Omitted tokens fall back to the dark theme.
   */
  public static function register(string $name, array $preset): void {
    static::$custom[$name] = $preset;
  }

  /**
   * Construct a theme.
   *
   * @param string $preset
   *   The preset name ("dark", "light" or a registered name; "default" is an
   *   alias for "dark").
   * @param array{styles?:array<string,string>,glyphs?:array<string,string>} $overrides
   *   Per-token overrides with optional 'styles' and 'glyphs' maps.
   * @param bool $color
   *   Whether colour is enabled.
   * @param int $width
   *   The frame width used for right-aligned badges.
   */
  public function __construct(string $preset = 'default', array $overrides = [], protected bool $color = TRUE, protected int $width = 76) {
    $resolved = static::preset($preset);
    $this->styles = array_merge($resolved['styles'], $overrides['styles'] ?? []);
    $this->glyphs = array_merge($resolved['glyphs'], $overrides['glyphs'] ?? []);
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

  /**
   * The resolved preset (styles + glyphs) for a name (falls back to dark).
   *
   * @param string $name
   *   The preset name.
   *
   * @return array{styles:array<string,string>,glyphs:array<string,string>}
   *   The resolved styles and glyphs, with any missing tokens filled from dark.
   */
  public static function preset(string $name): array {
    $glyphs = [
      'marker' => '❯',
      'indicator_up' => '▲',
      'indicator_down' => '▼',
      'separator' => '›',
      'arrow' => '›',
    ];

    $presets = [
      // Dark terminal theme (the default): bright foregrounds on a dark ground.
      'dark' => [
        'styles' => [
          'title' => '1;36',
          'breadcrumb' => '2',
          'label' => '',
          'value' => '32',
          'description' => '2',
          'marker' => '1;36',
          'badge' => '7',
          'cursor' => '1;7',
          'footer' => '2',
          'indicator' => '1;33',
        ],
        'glyphs' => $glyphs,
      ],
      // Light terminal theme: darker, higher-contrast foregrounds (bright
      // cyan/yellow wash out on a light background).
      'light' => [
        'styles' => [
          'title' => '1;34',
          'breadcrumb' => '2',
          'label' => '',
          'value' => '34',
          'description' => '2',
          'marker' => '1;34',
          'badge' => '7',
          'cursor' => '1;7',
          'footer' => '2',
          'indicator' => '35',
        ],
        'glyphs' => $glyphs,
      ],
    ];

    // Consumer-registered presets extend or override the built-ins.
    $all = array_merge($presets, static::$custom);

    // "default" is an alias for the dark theme.
    $name = $name === 'default' ? 'dark' : $name;
    $preset = $all[$name] ?? $presets['dark'];

    // Registered presets may omit styles or glyphs; missing tokens fall back
    // to the dark theme so every token resolves.
    return [
      'styles' => ($preset['styles'] ?? []) + $presets['dark']['styles'],
      'glyphs' => ($preset['glyphs'] ?? []) + $presets['dark']['glyphs'],
    ];
  }

}
