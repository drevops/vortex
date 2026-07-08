<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Condition\ConditionInterface;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;

/**
 * Handler for the "migration_image" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class MigrationImage extends AbstractFieldHandler {

  /**
   * Validate the collected value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string|null
   *   An error message, or NULL when valid.
   */
  public static function validate(mixed $value): ?string {
    return is_string($value) && Validate::isContainerImage($value) ? NULL : 'Please enter a valid container image name with an optional tag.';
  }

  /**
   * Normalize the collected value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return mixed
   *   The normalized value.
   */
  public static function transform(mixed $value): mixed {
    return is_string($value) ? trim($value) : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if (!empty($value)) {
      Env::writeValueDotenv('VORTEX_DB2_IMAGE', is_string($value) ? $value : '', $context->directory . '/.env');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'migration_image';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Migration database container image name and tag';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::Text;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'Use the "latest" tag for the latest version.';
  }

  /**
   * {@inheritdoc}
   */
  public static function when(): ?ConditionInterface {
    return new Condition('migration_fetch_source', eq: MigrationFetchSource::CONTAINER_REGISTRY);
  }

  /**
   * {@inheritdoc}
   */
  public static function derive(): ?Derive {
    return new Derive('{{org_machine_name}}/{{machine_name}}-data-migration:latest', 'lower');
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 100;
  }

}
