<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Util;

class NameDiscovery extends AbstractDiscovery {

  public function discover() {
    $value = Util::getComposerJsonValue('description', $this->config->getDstDir() . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/Drupal \d+ .* of ([0-9a-zA-Z\- ]+) for ([0-9a-zA-Z\- ]+)/', (string) $value, $matches) && !empty($matches[1])) {
      return $matches[1];
    }

    return NULL;
  }

}
