<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;

/**
 * Handler for the "webroot" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Webroot extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && Validate::isDirname($value) ? NULL : 'Please enter a valid webroot name: only lowercase letters, numbers, and underscores are allowed.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
    return is_string($value) ? rtrim($value, '/') : $value;
  }

}
