<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\File;
use DrevOps\Installer\PromptFields;
use DrevOps\Installer\Util;

class ModulePrefixDiscovery extends AbstractDiscovery {

  public function discover() {
    $webroot = $this->getAnswer(PromptFields::WEBROOT_CUSTOM);

    $locations = [
      $this->config->getDstDir() . sprintf('/%s/modules/custom/*_core', $webroot),
      $this->config->getDstDir() . sprintf('/%s/sites/all/modules/custom/*_core', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/*/modules/*_core', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/*/modules/custom/*_core', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/custom/*/modules/*_core', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/custom/*/modules/custom/*_core', $webroot),
    ];

    $path = File::findMatchingPath($locations);

    if (empty($path)) {
      return NULL;
    }

    $path = basename($path);

    return str_replace('_core', '', $path);
  }

}
