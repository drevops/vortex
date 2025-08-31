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
  public function label(): string {
    return 'Profile';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆, ⬇ and Space bar to select which Drupal profile to use.';
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::STANDARD => 'Standard',
      self::MINIMAL => 'Minimal',
      self::DEMO_UMAMI => 'Demo Umami',
      self::CUSTOM => 'Custom (next prompt)',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::STANDARD;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = $this->discoverName();

    if (!is_null($value)) {
      return in_array($value, [self::STANDARD, self::MINIMAL, self::DEMO_UMAMI]) ? $value : self::CUSTOM;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    // If user selected 'custom', use the ProfileCustom response instead.
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
   * Discover the profile name from the filesystem or environment.
   *
   * @return null|string|bool|array
   *   The profile name if found, NULL if not found.
   */
  public function discoverName(): null|string|bool|array {
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

}
