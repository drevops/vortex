<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

/**
 * Installer configuration.
 *
 * Installer config is a config of this installer script.
 *
 * @package DrevOps\VortexInstaller
 */
final class Config {

  const string ROOT = 'VORTEX_INSTALLER_ROOT_DIR';

  const string DST = 'VORTEX_INSTALLER_DST_DIR';

  const string TMP = 'VORTEX_INSTALLER_TMP_DIR';

  const string REPO = 'VORTEX_INSTALLER_TEMPLATE_REPO';

  const string REF = 'VORTEX_INSTALLER_TEMPLATE_REF';

  const string PROCEED = 'VORTEX_INSTALLER_PROCEED';

  const string IS_DEMO = 'VORTEX_INSTALLER_IS_DEMO';

  const string IS_DEMO_DB_DOWNLOAD_SKIP = 'VORTEX_INSTALLER_IS_DEMO_DB_DOWNLOAD_SKIP';

  const string IS_VORTEX_PROJECT = 'VORTEX_INSTALLER_IS_VORTEX_PROJECT';

  const string VERSION = 'VORTEX_INSTALLER_VERSION';

  const string NO_INTERACTION = 'VORTEX_INSTALLER_NO_INTERACTION';

  const string QUIET = 'VORTEX_INSTALLER_QUIET';

  const string NO_CLEANUP = 'VORTEX_INSTALLER_NO_CLEANUP';

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
