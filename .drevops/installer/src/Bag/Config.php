<?php

namespace DrevOps\Installer\Bag;

use DrevOps\Installer\Trait\ReadOnlyTrait;
use DrevOps\Installer\Trait\SingletonTrait;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Files;

/**
 *
 */
class Config extends AbstractBag {

  use SingletonTrait {
    SingletonTrait::__construct as private __singletonConstruct;
  }
  use ReadOnlyTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    self::__singletonConstruct();

    $this->collectFromEnv([
      // Destination directory.
      Env::INSTALLER_DST_DIR => getcwd(),
      // Internal version of DrevOps.
      Env::DREVOPS_VERSION => 'dev',
      // Flag to display install debug information.
      Env::INSTALLER_DEBUG => FALSE,
      // Flag to proceed with installation. If FALSE - the installation will only
      // print resolved values and will not proceed.
      Env::INSTALLER_INSTALL_PROCEED => TRUE,
      // Temporary directory to download and expand files to.
      Env::INSTALLER_TMP_DIR => Files::tempdir(),
      // Path to local DrevOps repository. If not provided - remote will be used.
      Env::INSTALLER_LOCAL_REPO => NULL,
      // Optional commit to download. If not provided, the latest release will be
      // downloaded.
      Env::INSTALLER_COMMIT => 'HEAD',
      // Internal flag to enforce DEMO mode. If not set, the demo mode will be
      // discovered automatically.
      Env::INSTALLER_DEMO_MODE => FALSE,
      // Internal flag to skip processing of the demo mode.
      Env::INSTALLER_DEMO_MODE_SKIP => FALSE,
    ]);
  }

  /**
   * Set installer configuration.
   *
   * Installer config is a config of this installer script. For configs of the
   * project being installed, @param $name.
   *
   * @param $value
   *
   * @see set_answer()
   *
   * @see init_config()
   */
  public function set(string $name, mixed $value): void {
    if ($this->isReadOnly()) {
      // You can choose to throw an exception or silently ignore.
      // Here, we're choosing to throw an exception.
      throw new \RuntimeException('Cannot modify a read-only config.');
    }
    parent::set($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function fromValues($values = []): AbstractBag {
    parent::fromValues($values);

    // Re-map 'path' to the destination directory.
    $path = $values['path'] ?? $this->get('path');
    if ($path) {
      $this->set(Env::INSTALLER_DST_DIR, $path);
    }

    // @todo Review and remove. There is no reason to load dotenv here.
    // DotEnv::loadDotenv($this->getDstDir() . '/.env');
    return $this;
  }

  /**
   * Shorthand to get the value of whether install should be quiet.
   *
   * @return mixed|null
   */
  public function isQuiet() {
    return $this->get('quiet', FALSE);
  }

  /**
   * Shorthand to get the value the destination directory.
   *
   * @return mixed|null
   */
  public function getDstDir(): string {
    return $this->get(Env::INSTALLER_DST_DIR, getcwd());
  }

  /**
   * Shorthand to get the value of the webroot.
   */
  public function getWebroot(): string {
    return $this->get(Env::WEBROOT, 'web');
  }

  protected function collectFromEnv(array $defaults = []): void {
    $constants = Env::getConstants();

    foreach ($constants as $name) {
      $this->set($name, Env::get($name, $defaults[$name] ?? NULL));
    }
  }

}
