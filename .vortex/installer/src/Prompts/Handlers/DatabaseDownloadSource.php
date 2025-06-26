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

  const NONE = 'none';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return Env::getFromDotenv('VORTEX_DB_DOWNLOAD_SOURCE', $this->dstDir);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $type = $this->getResponseAsString();

    File::replaceContentInFile($this->tmpDir . '/.env', '/VORTEX_DB_DOWNLOAD_SOURCE=.*/', 'VORTEX_DB_DOWNLOAD_SOURCE=' . $type);

    $types = [
      DatabaseDownloadSource::URL,
      DatabaseDownloadSource::FTP,
      DatabaseDownloadSource::ACQUIA,
      DatabaseDownloadSource::LAGOON,
      DatabaseDownloadSource::CONTAINER_REGISTRY,
    ];

    foreach ($types as $t) {
      $token = 'DB_DOWNLOAD_SOURCE_' . strtoupper($t);
      if ($t === $type) {
        File::removeTokenAsync('!' . $token);
      }
      else {
        File::removeTokenAsync($token);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '📡 Database source';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(): ?string {
    return 'The database can be downloaded as an exported dump file or pre-packaged in a container image.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(): ?array {
    return [
      self::URL => '🌍 URL download',
      self::FTP => '📂 FTP download',
      self::ACQUIA => '💧 Acquia backup',
      self::LAGOON => '🌊 Lagoon environment',
      self::CONTAINER_REGISTRY => '🐳 Container registry',
      self::NONE => '🚫 None',
    ];
  }

  /**
   * {@inheritdoc}
   * @param array &$options
   */
  public function optionsAlter(array &$options, array $responses): void {
    if (isset($responses[HostingProvider::id()])) {
      if ($responses[HostingProvider::id()] === HostingProvider::ACQUIA) {
        unset($options[self::LAGOON]);
      }

      if ($responses[HostingProvider::id()] === HostingProvider::LAGOON) {
        unset($options[self::ACQUIA]);
      }
    }
  }

  /**
   * {@inheritdoc}
   * @param mixed &$default
   */
  public function defaultAlter(mixed &$default, array $responses): void {
    if (isset($responses[HostingProvider::id()])) {
      $default = match ($responses[HostingProvider::id()]) {
        HostingProvider::ACQUIA => self::ACQUIA,
        HostingProvider::LAGOON => self::LAGOON,
        default => self::URL,
      };
    }

    $default = $this->discover() ?? self::URL;
  }

  /**
   * {@inheritdoc}
   */
  public function isConditional(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function condition(): ?callable {
    return fn(array $responses): bool => isset($responses[ProvisionType::id()]) &&
      $responses[ProvisionType::id()] !== ProvisionType::PROFILE;
  }

}
