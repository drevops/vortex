<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tui;

/**
 * Maps semantic roles to ANSI styles, with presets, overrides and no-color.
 *
 * Roles (title, breadcrumb, label, value, description, marker, badge, cursor,
 * footer, indicator) decouple the renderer from concrete colours. A preset
 * seeds the roles, per-role overrides refine them, and disabling colour makes
 * every style a no-op so the layout stays identical.
 *
 * @package DrevOps\Customizer\Tui
 */
class Theme {

  /**
   * The role => SGR map.
   *
   * @var array<string,string>
   */
  protected array $roles;

  /**
   * Construct a theme.
   *
   * @param string $preset
   *   The preset name (e.g. "default", "green").
   * @param array<string,string> $overrides
   *   Per-role SGR overrides.
   * @param bool $color
   *   Whether colour is enabled.
   */
  public function __construct(string $preset = 'default', array $overrides = [], protected bool $color = TRUE) {
    $this->roles = array_merge(static::preset($preset), $overrides);
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
    return $this->color ? ($this->roles[$role] ?? '') : '';
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
   * The SGR map for a preset (falls back to "default").
   *
   * @param string $name
   *   The preset name.
   *
   * @return array<string,string>
   *   The role => SGR map.
   */
  public static function preset(string $name): array {
    $presets = [
      'default' => [
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
      'green' => [
        'title' => '1;32',
        'breadcrumb' => '2',
        'label' => '',
        'value' => '1;32',
        'description' => '2',
        'marker' => '1;32',
        'badge' => '7',
        'cursor' => '1;7',
        'footer' => '2',
        'indicator' => '1;32',
      ],
    ];

    return $presets[$name] ?? $presets['default'];
  }

}
