<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Utils\Composer;

class MachineNameDiscovery extends AbstractDiscovery {

  public function discover() {
    $value = Composer::getJsonValue('name', $this->config->getDstDir() . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/([^\/]+)\/(.+)/', (string) $value, $matches) && !empty($matches[2])) {
      return $matches[2];
    }

    return NULL;
  }

}
