<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class DatabaseDownloadSource extends AbstractHandler {

  const URL = 'url';

  const FTP = 'ftp';

  const ACQUIA = 'acquia';

  const LAGOON = 'lagoon';

  const CONTAINER_REGISTRY = 'container_registry';

  const S3 = 's3';

  const NONE = 'none';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Database source';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇ to select the database download source.';
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
      self::CONTAINER_REGISTRY => 'Container registry',
      self::S3 => 'S3 bucket',
      self::NONE => 'None',
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
    return isset($responses[ProvisionType::id()]) && $responses[ProvisionType::id()] !== ProvisionType::PROFILE;
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
    return Env::getFromDotenv('VORTEX_DOWNLOAD_DB_SOURCE', $this->dstDir);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    Env::writeValueDotenv('VORTEX_DOWNLOAD_DB_SOURCE', $v, $t . '/.env');

    $types = [
      DatabaseDownloadSource::URL,
      DatabaseDownloadSource::FTP,
      DatabaseDownloadSource::ACQUIA,
      DatabaseDownloadSource::LAGOON,
      DatabaseDownloadSource::CONTAINER_REGISTRY,
      DatabaseDownloadSource::S3,
    ];

    foreach ($types as $type) {
      $token = 'DB_DOWNLOAD_SOURCE_' . strtoupper($type);
      if ($v === $type) {
        File::removeTokenAsync('!' . $token);
      }
      else {
        File::removeTokenAsync($token);
      }
    }
  }

}
