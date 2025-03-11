<?php

declare(strict_types=1);

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
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    $v = (string) $this->response;
    $t = $this->tmpDir;
    $w = $this->webroot;

    File::replaceContent($t . '/.env', '/DRUPAL_PROFILE=.*/', 'DRUPAL_PROFILE=' . $v);

    if (in_array($v, [self::STANDARD, self::MINIMAL, self::DEMO_UMAMI])) {
      File::rmdir(sprintf('%s/%s/profiles/your_site_profile', $t, $w));
      File::rmdir(sprintf('%s/%s/profiles/custom/your_site_profile', $t, $w));
      File::replaceContentInDir($t, $w . '/profiles/your_site_profile,', '');
      File::replaceContentInDir($t, $w . '/profiles/custom/your_site_profile,', '');
    }
    else {
      File::replaceContentInDir($t, 'your_site_profile', $v);
      File::renameInDir($t, 'your_site_profile', $v);
    }
  }

}
