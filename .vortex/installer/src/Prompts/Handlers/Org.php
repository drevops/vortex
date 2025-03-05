<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Composer;

class Org extends AbstractHandler {

  public function discover(): null|string|bool|iterable {
    $value = Composer::getJsonValue('description', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/Drupal \d+ .* of ([0-9a-zA-Z\- ]+) for ([0-9a-zA-Z\- ]+)/', (string) $value, $matches) && !empty($matches[2])) {
      return $matches[2];
    }

    return NULL;
  }


  public function process():void  {
    // @todo Implement this.
  }

}
