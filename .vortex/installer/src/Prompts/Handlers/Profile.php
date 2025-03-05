<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class Profile extends AbstractHandler {

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

    $name = File::findMatchingPath($locations, 'Drupal 11 profile implementation of');

    if (empty($name)) {
      return NULL;
    }

    $name = basename($name);

    return str_replace(['.info.yml', '.info'], '', $name);
  }

  public function process(): void {
    $webroot = $this->responses[WebrootCustom::id()];

    $core_profiles = [
      'standard',
      'minimal',
      'testing',
      'demo_umami',
    ];

    // For core profiles - remove custom profile and direct links to it.
    if (in_array($this->response, $core_profiles)) {
      File::rmdirRecursive(sprintf('%s/%s/profiles/your_site_profile', $this->tmpDir, $webroot));
      File::rmdirRecursive(sprintf('%s/%s/profiles/custom/your_site_profile', $this->tmpDir, $webroot));
      File::dirReplaceContent($webroot . '/profiles/your_site_profile,', '', $this->tmpDir);
      File::dirReplaceContent($webroot . '/profiles/custom/your_site_profile,', '', $this->tmpDir);
    }

    File::dirReplaceContent('your_site_profile', $this->response, $this->tmpDir);
  }

}
