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
final class Config {

  const ROOT = 'VORTEX_INSTALL_ROOT_DIR';

  const DST = 'VORTEX_INSTALL_DST_DIR';

  const TMP = 'VORTEX_INSTALL_TMP_DIR';

  const REPO = 'VORTEX_INSTALL_REPO';

  const REF = 'VORTEX_INSTALL_REF';

  const PROCEED = 'VORTEX_INSTALL_PROCEED';

  const IS_DEMO_MODE = 'VORTEX_INSTALL_IS_DEMO_MODE';

  const DEMO_MODE_SKIP = 'VORTEX_INSTALL_DEMO_SKIP';

  const IS_VORTEX_PROJECT = 'VORTEX_INSTALL_IS_VORTEX_PROJECT';

  const VERSION = 'VORTEX_INSTALL_VERSION';

  const NO_INTERACTION = 'VORTEX_INSTALL_NO_INTERACTION';

  const QUIET = 'VORTEX_INSTALL_QUIET';

  /**
   * Store of configuration values.
   *
   * @var array<string,mixed>
   */
  protected array $store = [];

  public function __construct(?string $root = NULL, ?string $dst = NULL, ?string $tmp = NULL) {
    $this->set(self::ROOT, $root ?: File::cwd());
    $this->set(self::DST, $dst ?: $this->get(self::ROOT), TRUE);
    $this->set(self::TMP, $tmp ?: File::tmpdir());
  }

  /**
   * Create a new instance of the config from a JSON string.
   */
  public static function fromString(string $json): static {
    $config = json_decode($json, TRUE);

    if (!is_array($config)) {
      throw new \RuntimeException('Invalid configuration JSON string provided.');
    }

    $instance = new self();

    foreach ($config as $key => $value) {
      if (!is_string($key)) {
        throw new \RuntimeException(sprintf('Invalid key "%s" in JSON string provided.', $key));
      }

      $instance->set(strtoupper($key), $value);
    }

    return $instance;
  }

  public function get(string $name, mixed $default = NULL): mixed {
    return $this->store[$name] ?? $default;
  }

  public function set(string $name, mixed $value, bool $skip_env = FALSE): static {
    if (!$skip_env) {
      // Environment variables always take precedence.
      $value = Env::get($name, $value);
    }

    if (!is_null($value)) {
      $this->store[$name] = $value;
    }

    return $this;
  }

  public function getRoot(): ?string {
    return $this->get(self::ROOT);
  }

  public function getDst(): ?string {
    return $this->get(self::DST);
  }

  /**
   * Shorthand to get the value of whether install should be quiet.
   */
  public function isQuiet(): bool {
    return (bool) $this->get(self::QUIET, FALSE);
  }

  public function setQuiet(bool $value = TRUE): void {
    $this->set(self::QUIET, $value);
  }

  public function getNoInteraction(): bool {
    return (bool) $this->get(self::NO_INTERACTION, FALSE);
  }

  public function setNoInteraction(bool $value = TRUE): void {
    $this->set(self::NO_INTERACTION, $value);
  }

  public function isVortexProject(): bool {
    return (bool) $this->get(self::IS_VORTEX_PROJECT, FALSE);
  }

}
