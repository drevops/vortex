<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;

/**
 * Handler for the "org" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Org extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && Validate::isFilledLabel($value) ? NULL : 'Please enter a valid organization name.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
    return is_string($value) ? trim($value) : $value;
  }

}
