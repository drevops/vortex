<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Composer;

class MachineName extends AbstractHandler {

  public function discover(): null|string|bool|iterable {
    $value = Composer::getJsonValue('name', $this->dstDir . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/([^\/]+)\/(.+)/', (string) $value, $matches) && !empty($matches[2])) {
      return $matches[2];
    }

    return NULL;
  }

  public function process():void  {
    // @todo Implement this.
  }


}
