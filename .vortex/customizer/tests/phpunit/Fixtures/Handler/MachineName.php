<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Fixtures\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;

/**
 * Test fixture: a handler auto-discovered for the "machine_name" field.
 *
 * @package DrevOps\Customizer\Tests\Fixtures\Handler
 */
class MachineName extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && $value !== '' ? NULL : 'A machine name is required.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
    return is_string($value) ? strtolower($value) : $value;
  }

}
