<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\InstallerConfig;

abstract class AbstractDiscovery {

  public function __construct(protected InstallerConfig $config, protected array $responses) {
  }

  abstract public function discover();

  // @todo: Rename to getResponse().
  public function getAnswer($name) {
    return $this->responses[$name] ?? NULL;
  }
}
