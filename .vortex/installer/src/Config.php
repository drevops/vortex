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

}
