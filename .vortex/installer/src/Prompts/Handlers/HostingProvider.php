<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\JsonManipulator;

class HostingProvider extends AbstractHandler {

  const NONE = 'none';

  const LAGOON = 'lagoon';

  const ACQUIA = 'acquia';

  const OTHER = 'other';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Hosting provider';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆, ⬇ and Space bar to select your hosting provider.';
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
      self::ACQUIA => 'Acquia Cloud',
      self::LAGOON => 'Lagoon',
      self::OTHER => 'Other',
      self::NONE => 'None',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return 'none';
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (is_readable($this->dstDir . '/hooks') || Env::getFromDotenv('VORTEX_DB_DOWNLOAD_SOURCE', $this->dstDir) === DatabaseDownloadSource::ACQUIA) {
      return self::ACQUIA;
    }

    if (is_readable($this->dstDir . '/.lagoon.yml')) {
      return self::LAGOON;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;
    $w = $this->webroot;

    if ($v === static::ACQUIA) {
      File::removeTokenAsync('!HOSTING_ACQUIA');
      File::removeTokenAsync('!SETTINGS_PROVIDER_ACQUIA');

      $this->removeLagoon();
    }
    elseif ($v === static::LAGOON) {
      File::removeTokenAsync('!HOSTING_LAGOON');
      File::removeTokenAsync('!SETTINGS_PROVIDER_LAGOON');

      $this->removeAcquia();

      @unlink(sprintf('%s/%s/.htaccess', $t, $w));

      $cj = JsonManipulator::fromFile($t . '/composer.json');
      $cj->addLink('require', 'drupal/lagoon_logs', '^3', TRUE);
      file_put_contents($t . '/composer.json', $cj->getContents());
    }
    else {
      $this->removeAcquia();
      $this->removeLagoon();

      File::removeTokenAsync('HOSTING');

      @unlink(sprintf('%s/%s/.htaccess', $t, $w));
    }
  }

  protected function removeAcquia(): void {
    File::rmdir($this->tmpDir . '/hooks');
    @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.acquia.php', $this->tmpDir, $this->webroot));

    File::removeTokenAsync('HOSTING_ACQUIA');
    File::removeTokenAsync('SETTINGS_PROVIDER_ACQUIA');
  }

  protected function removeLagoon(): void {
    @unlink($this->tmpDir . '/drush/sites/lagoon.site.yml');
    @unlink($this->tmpDir . '/.lagoon.yml');
    @unlink($this->tmpDir . '/.github/workflows/close-pull-request.yml');
    @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.lagoon.php', $this->tmpDir, $this->webroot));

    File::removeTokenAsync('HOSTING_LAGOON');
    File::removeTokenAsync('SETTINGS_PROVIDER_LAGOON');
  }

  /**
   * {@inheritdoc}
   */
  public function postInstall(): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function postBuild(string $result): ?string {
    if ($this->isInstalled()) {
      return NULL;
    }

    $v = $this->getResponseAsString();

    if ($v === self::ACQUIA) {
      return 'Setup Acquia hosting:' . PHP_EOL
        . '  https://www.vortextemplate.com/docs/hosting/acquia#onboarding' . PHP_EOL
        . PHP_EOL;
    }

    if ($v === self::LAGOON) {
      return 'Setup Lagoon hosting:' . PHP_EOL
        . '  https://www.vortextemplate.com/docs/hosting/lagoon#onboarding' . PHP_EOL
        . PHP_EOL;
    }

    return NULL;
  }

}
