<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

/**
 * Installer configuration.
 *
 * Installer config is a config of this installer script.
 *
 * @package DrevOps\Installer
 */
class InstallerConfig {

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
    return (bool) $this->get('QUIET', FALSE);
  }

  /**
   * Shorthand to get the value of VORTEX_INSTALL_DEBUG.
   */
  public function isInstallDebug(): bool {
    return (bool) $this->get('VORTEX_INSTALL_DEBUG', FALSE);
  }

  public function setQuiet(bool $value = TRUE): void {
    $this->set('QUIET', $value);
  }

  public static function fromString(string $config): static {
    $config = json_decode($config, TRUE);

    if (!is_array($config)) {
      throw new \RuntimeException('Invalid JSON string provided in --config option.');
    }

    $instance = new self();

    foreach ($config as $key => $value) {
      if (!is_string($key)) {
        throw new \RuntimeException(sprintf('Invalid key "%s" in JSON string provided in --config option.', $key));
      }

      $instance->set(strtoupper($key), $value);
    }

    return $instance;
  }

}
