<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Files;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Profile processor.
 */
class ProfileProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 20;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    $webroot = $config->getWebroot();
    $profile = $config->get('profile');

    // For core profiles - remove custom profile and direct links to it.
    if (in_array($profile, $this->drupalCoreProfiles())) {
      Files::rmdirRecursive(sprintf('%s/%s/profiles/your_site_profile', $dir, $webroot));
      Files::rmdirRecursive(sprintf('%s/%s/profiles/custom/your_site_profile', $dir, $webroot));
      Files::dirReplaceContent($webroot . '/profiles/your_site_profile,', '', $dir);
      Files::dirReplaceContent($webroot . '/profiles/custom/your_site_profile,', '', $dir);
    }
    Files::dirReplaceContent('your_site_profile', $profile, $dir);
  }

  protected static function drupalCoreProfiles(): array {
    return [
      'standard',
      'minimal',
      'testing',
      'demo_umami',
    ];
  }

}
