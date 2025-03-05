<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Utils\File;

class ModulePrefixHandler extends AbstractHandler {

  public function discover(): ?string {
    $locations = [
      $this->config->getDst() . sprintf('/%s/modules/custom/*_core', $this->webroot),
      $this->config->getDst() . sprintf('/%s/sites/all/modules/custom/*_core', $this->webroot),
      $this->config->getDst() . sprintf('/%s/profiles/*/modules/*_core', $this->webroot),
      $this->config->getDst() . sprintf('/%s/profiles/*/modules/custom/*_core', $this->webroot),
      $this->config->getDst() . sprintf('/%s/profiles/custom/*/modules/*_core', $this->webroot),
      $this->config->getDst() . sprintf('/%s/profiles/custom/*/modules/custom/*_core', $this->webroot),
    ];

    $path = File::findMatchingPath($locations);

    if (empty($path)) {
      return NULL;
    }

    $path = basename($path);

    return str_replace('_core', '', $path);
  }

  public function process():void  {
    // @todo Implement this.
  }

}
