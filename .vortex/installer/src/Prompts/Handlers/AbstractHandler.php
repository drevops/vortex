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
}
