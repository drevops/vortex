<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class HostingProvider extends AbstractHandler {

  const NONE = 'none';

  const LAGOON = 'lagoon';

  const ACQUIA = 'acquia';

  const OTHER = 'other';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '☁️ Hosting provider';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Select the hosting provider where the project is hosted. The web root directory will be set accordingly.';
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
      self::ACQUIA => '💧 Acquia Cloud',
      self::LAGOON => '🌊 Lagoon',
      self::OTHER => '🧩 Other',
      self::NONE => '🚫 None',
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

    if ($v === static::ACQUIA) {
      File::removeTokenAsync('!HOSTING_ACQUIA');
      File::removeTokenAsync('!SETTINGS_PROVIDER_ACQUIA');
      $this->removeLagoon();
    }
    elseif ($v === static::LAGOON) {
      File::removeTokenAsync('!HOSTING_LAGOON');
      File::removeTokenAsync('!SETTINGS_PROVIDER_LAGOON');
      $this->removeAcquia();
    }
    else {
      $this->removeAcquia();
      $this->removeLagoon();
      File::removeTokenAsync('HOSTING');
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

}
