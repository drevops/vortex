<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\File;
use DrevOps\Installer\PromptFields;

class ProfileDiscovery extends AbstractDiscovery {

  public function discover() {
    $webroot = $this->getAnswer(PromptFields::WEBROOT_CUSTOM);

    if ($this->isInstalled()) {
      $name = $this->getValueFromDstDotenv('DRUPAL_PROFILE');
      if (!empty($name)) {
        return $name;
      }
    }

    $locations = [
      $this->config->getDstDir() . sprintf('/%s/profiles/*/*.info', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/*/*.info.yml', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/custom/*/*.info', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/custom/*/*.info.yml', $webroot),
    ];

    $name = File::findMatchingPath($locations, 'Drupal 11 profile implementation of');

    if (empty($name)) {
      return NULL;
    }

    $name = basename($name);

    return str_replace(['.info.yml', '.info'], '', $name);
  }

}
