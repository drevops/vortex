<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tui;

/**
 * Maps semantic tokens to ANSI styles and glyphs, with presets and overrides.
 *
 * A token has two overridable parts: a style (the SGR colour for a role) and,
 * for the decorative elements, a glyph (the literal character - marker, scroll
 * indicators, separators). A preset seeds both; per-token overrides and
 * consumer-registered presets refine them; disabling colour makes every style a
 * no-op while keeping the glyphs and layout identical.
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
   * Lets a consumer extend the built-in themes with their own palette and
   * glyphs without modifying the customizer. A registered name overrides a
   * built-in of the same name.
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
   *   The preset name ("dark", "light" or a registered name; "default" is
   *   an alias for "dark").
   * @param array{styles?:array<string,string>,glyphs?:array<string,string>} $overrides
   *   Per-token overrides with optional 'styles' and 'glyphs' maps.
   * @param bool $color
   *   Whether colour is enabled.
   */
  public function __construct(string $preset = 'default', array $overrides = [], protected bool $color = TRUE) {
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
