<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class MigrationFetchSource extends AbstractHandler {

  const URL = 'url';

  const FTP = 'ftp';

  const ACQUIA = 'acquia';

  const LAGOON = 'lagoon';

  const CONTAINER_REGISTRY = 'container_registry';

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
    return 'Use ⬆ and ⬇ to select the migration database fetch source.';
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
  public function dependsOn(): ?array {
    return [Migration::id() => [TRUE]];
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
    return Env::getFromDotenv('VORTEX_FETCH_DB2_SOURCE', $this->dstDir);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $t = $this->tmpDir;

    $v = NULL;
    if (!empty($this->response)) {
      $v = $this->getResponseAsString();

      Env::writeValueDotenv('VORTEX_FETCH_DB2_SOURCE', $v, $t . '/.env');

      // Lagoon identifies environments by branch name; the production branch
      // is `main`. The shared default (`prod`) is correct for Acquia only.
      if ($v === self::LAGOON) {
        Env::writeValueDotenv('VORTEX_FETCH_DB2_ENVIRONMENT', 'main', $t . '/.env');
      }
    }

    $types = [
      MigrationFetchSource::URL,
      MigrationFetchSource::FTP,
      MigrationFetchSource::ACQUIA,
      MigrationFetchSource::LAGOON,
      MigrationFetchSource::CONTAINER_REGISTRY,
      MigrationFetchSource::S3,
    ];

    foreach ($types as $type) {
      $token = 'MIGRATION_DB_FETCH_SOURCE_' . strtoupper($type);
      if ($v === $type) {
        File::removeTokenAsync('!' . $token);
      }
      else {
        File::removeTokenAsync($token);
      }
    }

    // Gates content required only for the hosting-connected fetch sources.
    if ($v !== self::ACQUIA && $v !== self::LAGOON) {
      File::removeTokenAsync('MIGRATION_DB_FETCH_SOURCE_ACQUIA_LAGOON');
    }
  }

}
