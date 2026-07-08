<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Condition\ConditionInterface;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "database_fetch_source" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class DatabaseFetchSource extends AbstractFieldHandler implements OptionsInterface {

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
  public function process(Field $field, mixed $value, Context $context): void {
    $source = is_string($value) ? $value : '';

    Env::writeValueDotenv('VORTEX_FETCH_DB_SOURCE', $source, $context->directory . '/.env');

    // Lagoon identifies environments by branch name; the production branch
    // is `main`. The shared default (`prod`) is correct for Acquia only.
    if ($source === 'lagoon') {
      Env::writeValueDotenv('VORTEX_FETCH_DB_ENVIRONMENT', 'main', $context->directory . '/.env');
    }

    $types = ['url', 'ftp', 'acquia', 'lagoon', 'container_registry', 's3'];

    foreach ($types as $type) {
      $token = 'DB_FETCH_SOURCE_' . strtoupper($type);
      if ($source === $type) {
        File::removeTokenAsync('!' . $token);
      }
      else {
        File::removeTokenAsync($token);
      }
    }

    // Gates content required when either the primary or the migration
    // database is fetched from Lagoon.
    $migration_source = $context->answers['migration_fetch_source'] ?? NULL;

    if ($source !== 'lagoon' && $migration_source !== 'lagoon') {
      File::removeTokenAsync('DB_FETCH_ANY_SOURCE_LAGOON');
    }

    // Gates content required only for the hosting-connected fetch sources.
    if ($source !== 'acquia' && $source !== 'lagoon') {
      File::removeTokenAsync('DB_FETCH_SOURCE_HOSTED');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::URL => 'URL download',
      self::FTP => 'FTP download',
      self::ACQUIA => 'Acquia backup',
      self::LAGOON => 'Lagoon environment',
      self::CONTAINER_REGISTRY => 'Container registry',
      self::S3 => 'S3 bucket',
      self::NONE => 'None',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'database_fetch_source';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Database source';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::Select;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'Where the database dump is fetched from.';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return fn (Context $c): string => match ($c->answers['hosting_provider'] ?? NULL) { HostingProvider::ACQUIA => self::ACQUIA, HostingProvider::LAGOON => self::LAGOON, default => self::URL };
  }

  /**
   * {@inheritdoc}
   */
  public static function when(): ?ConditionInterface {
    return new Condition('provision_type', eq: ProvisionType::DATABASE);
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 140;
  }

}
