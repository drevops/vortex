<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class Profile extends AbstractHandler {

  const STANDARD = 'standard';

  const MINIMAL = 'minimal';

  const DEMO_UMAMI = 'demo_umami';

  const CUSTOM = 'custom';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if ($this->isInstalled()) {
      $name = Env::getFromDotenv('DRUPAL_PROFILE', $this->dstDir);
      if (!empty($name)) {
        return $name;
      }
    }

    $locations = [
      $this->dstDir . sprintf('/%s/profiles/*/*.info', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/*/*.info.yml', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/*.info', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/*.info.yml', $this->webroot),
    ];

    $path = File::findMatchingPath($locations, 'Drupal 11 profile implementation of');

    if (empty($path)) {
      return NULL;
    }

    return str_replace(['.info.yml', '.info'], '', basename($path));
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if ($this->response !== static::CUSTOM) {
      File::rmdirRecursive(sprintf('%s/%s/profiles/your_site_profile', $this->tmpDir, $this->webroot));
      File::rmdirRecursive(sprintf('%s/%s/profiles/custom/your_site_profile', $this->tmpDir, $this->webroot));
      File::dirReplaceContent($this->webroot . '/profiles/your_site_profile,', '', $this->tmpDir);
      File::dirReplaceContent($this->webroot . '/profiles/custom/your_site_profile,', '', $this->tmpDir);
    }
  }

}
