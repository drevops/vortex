<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;

class DatabaseFetchStore extends AbstractHandler {

  const NONE = 'none';

  const FTP = 'ftp';

  const S3 = 's3';

  /**
   * Database sources that a store can be filled from.
   */
  const SOURCES = [
    DatabaseFetchSource::URL,
    DatabaseFetchSource::FTP,
    DatabaseFetchSource::ACQUIA,
    DatabaseFetchSource::LAGOON,
    DatabaseFetchSource::S3,
  ];

  /**
   * {@inheritdoc}
   */
  public static function processWeight(): int {
    return 145;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Database store';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇ to select where developers fetch the sanitized database from.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    $options = [
      self::NONE => 'None, fetch from the source',
      self::FTP => 'FTP server',
      self::S3 => 'S3 bucket',
    ];

    // The nightly job fetches from the source and pushes to the store, so
    // the 2 must differ.
    if (isset($responses[DatabaseFetchSource::id()]) && is_string($responses[DatabaseFetchSource::id()])) {
      unset($options[$responses[DatabaseFetchSource::id()]]);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function dependsOn(): ?array {
    return [DatabaseFetchSource::id() => self::SOURCES];
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRun(array $responses): bool {
    return isset($responses[DatabaseFetchSource::id()]) && in_array($responses[DatabaseFetchSource::id()], self::SOURCES, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::NONE;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $value = Env::getFromDotenv('VORTEX_FETCH_DB_STORE', $this->destinationDir);

    return in_array($value, [self::FTP, self::S3], TRUE) ? $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $t = $this->tmpDir;

    $v = $this->shouldRun($this->responses) && in_array($this->response, [self::FTP, self::S3], TRUE) && $this->response !== $this->responses[DatabaseFetchSource::id()] ? $this->getResponseAsString() : '';

    Env::writeValueDotenv('VORTEX_FETCH_DB_STORE', $v, $t . '/.env');
  }

}
