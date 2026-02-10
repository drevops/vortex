<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class MigrationDownloadSource extends AbstractHandler {

  const URL = 'url';

  const FTP = 'ftp';

  const ACQUIA = 'acquia';

  const LAGOON = 'lagoon';

  const S3 = 's3';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Migration database source';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇ to select the migration database download source.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    $options = [
      self::URL => 'URL download',
      self::FTP => 'FTP download',
      self::ACQUIA => 'Acquia backup',
      self::LAGOON => 'Lagoon environment',
      self::S3 => 'S3 bucket',
    ];

    if (isset($responses[HostingProvider::id()])) {
      if ($responses[HostingProvider::id()] === HostingProvider::ACQUIA) {
        unset($options[self::LAGOON]);
      }

      if ($responses[HostingProvider::id()] === HostingProvider::LAGOON) {
        unset($options[self::ACQUIA]);
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRun(array $responses): bool {
    return isset($responses[Migration::id()]) && $responses[Migration::id()] === TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    if (isset($responses[HostingProvider::id()])) {
      return match ($responses[HostingProvider::id()]) {
        HostingProvider::ACQUIA => self::ACQUIA,
        HostingProvider::LAGOON => self::LAGOON,
        default => self::URL,
      };
    }

    return self::URL;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return Env::getFromDotenv('VORTEX_DOWNLOAD_DB2_SOURCE', $this->dstDir);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $t = $this->tmpDir;

    $v = NULL;
    if (!empty($this->response)) {
      $v = $this->getResponseAsString();

      Env::writeValueDotenv('VORTEX_DOWNLOAD_DB2_SOURCE', $v, $t . '/.env');
    }

    $types = [
      MigrationDownloadSource::URL,
      MigrationDownloadSource::FTP,
      MigrationDownloadSource::ACQUIA,
      MigrationDownloadSource::LAGOON,
      MigrationDownloadSource::S3,
    ];

    foreach ($types as $type) {
      $token = 'MIGRATION_DB_DOWNLOAD_SOURCE_' . strtoupper($type);
      if ($v === $type) {
        File::removeTokenAsync('!' . $token);
      }
      else {
        File::removeTokenAsync($token);
      }
    }
  }

}
