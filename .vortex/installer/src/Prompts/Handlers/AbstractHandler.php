<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\InstallerConfig;

abstract class AbstractHandler implements HandlerInterface {

  public function __construct(protected InstallerConfig $config, protected array $responses) {
  }

  abstract public function discover();

  abstract public function process(array $responses, string $dir):void;

  // @todo: Rename to getResponse().
  public function getAnswer($name) {
    return $this->responses[$name] ?? NULL;
  }


  /**
   * Check that Vortex is installed for this project.
   *
   * @todo Move to another place.
   */
  protected function isInstalled(): bool {
    $path = $this->config->getDstDir() . DIRECTORY_SEPARATOR . 'README.md';

    if (!file_exists($path)) {
      return FALSE;
    }

    $content = file_get_contents($path);
    if (!$content) {
      return FALSE;
    }

    return (bool) preg_match('/badge\/Vortex\-/', $content);
  }

}
