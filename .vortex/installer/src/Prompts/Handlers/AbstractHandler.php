<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Config;

abstract class AbstractHandler implements HandlerInterface {

  public function __construct(protected Config $config, protected array $responses) {
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
    return $this->config->isVortexProject();
  }

}
