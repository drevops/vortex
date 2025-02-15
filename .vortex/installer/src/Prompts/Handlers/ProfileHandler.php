<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class ProfileHandler extends AbstractHandler {

  public function discover() {
    $webroot = $this->getAnswer(PromptFields::WEBROOT_CUSTOM);

    if ($this->isInstalled()) {
      $name = Env::getFromDstDotenv('DRUPAL_PROFILE');
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

  public function process(array $responses, string $dir): void {
    $webroot = $this->getAnswer('webroot');
    // For core profiles - remove custom profile and direct links to it.
    if (in_array($this->getAnswer('profile'), $this->drupalCoreProfiles())) {
      File::rmdirRecursive(sprintf('%s/%s/profiles/your_site_profile', $dir, $webroot));
      File::rmdirRecursive(sprintf('%s/%s/profiles/custom/your_site_profile', $dir, $webroot));
      File::dirReplaceContent($webroot . '/profiles/your_site_profile,', '', $dir);
      File::dirReplaceContent($webroot . '/profiles/custom/your_site_profile,', '', $dir);
    }
    File::dirReplaceContent('your_site_profile', $this->getAnswer('profile'), $dir);
  }

  /**
   * Get core profiles names.
   *
   * @return array<int, string>
   *   Array of core profiles names.
   */
  protected function drupalCoreProfiles(): array {
    return [
      'standard',
      'minimal',
      'testing',
      'demo_umami',
    ];
  }

}
