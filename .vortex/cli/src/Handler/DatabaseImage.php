<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;

/**
 * Handler for the "database_image" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class DatabaseImage extends AbstractHandler implements FieldInterface {

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
      Env::writeValueDotenv('VORTEX_DB_IMAGE', is_string($value) ? $value : '', $context->directory . '/.env');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->text('database_image', 'Database container image name and tag')
      ->description('Use the "latest" tag for the latest version.')
      ->when(new Condition('database_fetch_source', eq: DatabaseFetchSource::CONTAINER_REGISTRY))
      ->derive(new Derive('{{org_machine_name}}/{{machine_name}}-data:latest', 'lower'))
      ->weight(130);
  }

}
