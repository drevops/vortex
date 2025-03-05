<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class Profile extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if ($this->isInstalled()) {
      $path = Env::getFromDotenv('DRUPAL_PROFILE', $this->dstDir);
      if (!empty($path)) {
        return $path;
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
    $core_profiles = [
      'standard',
      'minimal',
      'testing',
      'demo_umami',
    ];

    // For core profiles - remove custom profile and direct links to it.
    if (in_array($this->response, $core_profiles)) {
      File::rmdirRecursive(sprintf('%s/%s/profiles/your_site_profile', $this->tmpDir, $this->webroot));
      File::rmdirRecursive(sprintf('%s/%s/profiles/custom/your_site_profile', $this->tmpDir, $this->webroot));
      File::dirReplaceContent($this->webroot . '/profiles/your_site_profile,', '', $this->tmpDir);
      File::dirReplaceContent($this->webroot . '/profiles/custom/your_site_profile,', '', $this->tmpDir);
    }

    File::dirReplaceContent('your_site_profile', $this->response, $this->tmpDir);
  }

}
