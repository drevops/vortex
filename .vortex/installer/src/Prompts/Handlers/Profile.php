<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

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
      $value = Env::getFromDotenv('DRUPAL_PROFILE', $this->dstDir);
      if (!empty($value)) {
        return $value;
      }
    }

    $locations = [
      $this->dstDir . sprintf('/%s/profiles/*/*.info', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/*/*.info.yml', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/*.info', $this->webroot),
      $this->dstDir . sprintf('/%s/profiles/custom/*/*.info.yml', $this->webroot),
    ];

    $path = File::findMatchingPath($locations);

    if (empty($path)) {
      return NULL;
    }

    return str_replace(['.info.yml', '.info'], '', basename($path));
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;
    $w = $this->webroot;

    File::replaceContentInFile($t . '/.env', '/DRUPAL_PROFILE=.*/', 'DRUPAL_PROFILE=' . $v);

    if (in_array($v, [self::STANDARD, self::MINIMAL, self::DEMO_UMAMI])) {
      File::rmdir(sprintf('%s/%s/profiles/your_site_profile', $t, $w));
      File::rmdir(sprintf('%s/%s/profiles/custom/your_site_profile', $t, $w));

      File::replaceContentAsync([
        '/profiles/your_site_profile,' => '',
        '/profiles/custom/your_site_profile,' => '',
      ]);
    }
    else {
      File::replaceContentAsync('your_site_profile', $v);
      File::renameInDir($t, 'your_site_profile', $v);
    }
  }

}
