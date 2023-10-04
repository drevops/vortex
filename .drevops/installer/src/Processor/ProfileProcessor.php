<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Files;
use Symfony\Component\Console\Output\OutputInterface;

class ProfileProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 20;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output) {
    $webroot = $config->getWebroot();
    $profile = $config->get('profile');

    // For core profiles - remove custom profile and direct links to it.
    if (in_array($profile, $this->drupalCoreProfiles())) {
      Files::rmdirRecursive("$dir/$webroot/profiles/your_site_profile");
      Files::rmdirRecursive("$dir/$webroot/profiles/custom/your_site_profile");
      Files::dirReplaceContent("$webroot/profiles/your_site_profile,", '', $dir);
      Files::dirReplaceContent("$webroot/profiles/custom/your_site_profile,", '', $dir);
    }
    Files::dirReplaceContent('your_site_profile', $profile, $dir);
  }

  protected static function drupalCoreProfiles() {
    return [
      'standard',
      'minimal',
      'testing',
      'demo_umami',
    ];
  }

}
