<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;

/**
 * Handler for the "database_image" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class DatabaseImage extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && Validate::isContainerImage($value) ? NULL : 'Please enter a valid container image name with an optional tag.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
    return is_string($value) ? trim($value) : $value;
  }

}
