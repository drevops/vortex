<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Utils\Composer;

class OrgDiscovery extends AbstractDiscovery {

  public function discover() {
    $value = Composer::getJsonValue('description', $this->config->getDstDir() . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/Drupal \d+ .* of ([0-9a-zA-Z\- ]+) for ([0-9a-zA-Z\- ]+)/', (string) $value, $matches) && !empty($matches[2])) {
      return $matches[2];
    }

    return NULL;
  }

}
