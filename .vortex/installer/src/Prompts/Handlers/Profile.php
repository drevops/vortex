<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Converter;
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

    // If user selected 'custom', use the ProfileCustom response instead
    if ($v === self::CUSTOM && isset($this->responses['profile_custom'])) {
      $v = $this->responses['profile_custom'];
    }

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

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '🧾 Profile';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(): ?string {
    return 'Select which profile to use';
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): ?array {
    return [
      self::STANDARD => 'Standard',
      self::MINIMAL => 'Minimal',
      self::DEMO_UMAMI => 'Demo Umami',
      self::CUSTOM => 'Custom',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired(): bool {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function default(): mixed {
    $discovered = $this->discover();
    if (!empty($discovered)) {
      // If we discovered a standard profile, return it
      if (in_array($discovered, [self::STANDARD, self::MINIMAL, self::DEMO_UMAMI])) {
        return $discovered;
      }
      // If we discovered a custom profile, select "Custom" option
      return self::CUSTOM;
    }
    return self::STANDARD;
  }


}
