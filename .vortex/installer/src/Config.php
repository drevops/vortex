<?php

declare(strict_types=1);

namespace DrevOps\Installer;

/**
 * Installer configuration.
 *
 * Installer config is a config of this installer script. For configs of the
 * project being installed, @see get_answer().
 *
 * @package DrevOps\Installer
 */
class Config {

  /**
   * Installer configuration.
   *
   * @var array<string,mixed>
   */
  protected array $config = [];

  /**
   * Get a configuration value or default.
   */
  public function get(string $name, mixed $default = NULL): mixed {
    return $this->config[$name] ?? $default;
  }

  /**
   * Set a configuration value.
   */
  public function set(string $name, mixed $value): void {
    if (!is_null($value)) {
      $this->config[$name] = $value;
    }
  }

  public function getDstDir(): ?string {
    return $this->get('VORTEX_INSTALL_DST_DIR');
  }

  /**
   * Shorthand to get the value of whether install should be quiet.
   */
  public function isQuiet(): bool {
    return (bool) $this->get('quiet', FALSE);
  }

  /**
   * Shorthand to get the value of VORTEX_INSTALL_DEBUG.
   */
  public function isInstallDebug(): bool {
    return (bool) $this->get('VORTEX_INSTALL_DEBUG', FALSE);
  }

}
