<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class ModulePrefix extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $locations = [
      $this->dstDir . sprintf('/%s/modules/custom/*_core', $this->webroot),
      $this->dstDir . sprintf('/%s/sites/all/modules/custom/*_core', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/*/modules/*_core', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/*/modules/custom/*_core', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/modules/*_core', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/modules/custom/*_core', $this->webroot),
    ];

    $path = File::findMatchingPath($locations);

    return !empty($path) ? str_replace('_core', '', basename($path)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // @todo Implement this.
  }

}
