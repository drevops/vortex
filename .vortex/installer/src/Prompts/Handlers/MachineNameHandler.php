<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Composer;

class MachineNameHandler extends AbstractHandler {

  public function discover() {
    $value = Composer::getJsonValue('name', $this->config->getDst() . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/([^\/]+)\/(.+)/', (string) $value, $matches) && !empty($matches[2])) {
      return $matches[2];
    }

    return NULL;
  }

  public function process(array $responses, string $dir):void  {
    // @todo Implement this.
  }


}
