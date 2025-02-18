<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Composer;

class OrgMachineNameHandler extends AbstractHandler {

  public function discover() {
    $value = Composer::getJsonValue('name', $this->config->getDst() . DIRECTORY_SEPARATOR . 'composer.json');

    if ($value && preg_match('/([^\/]+)\/(.+)/', (string) $value, $matches) && !empty($matches[1])) {
      return $matches[1];
    }

    return NULL;
  }

}
