<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;

/**
 * Handler for the "name" question, auto-discovered by the customizer engine.
 *
 * Demonstrates the thin-CLI pattern: the customizer provides the base class and
 * lifecycle; this handler carries only the project-specific behaviour.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Name extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && trim($value) !== '' ? NULL : 'The site name is required.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
    return is_string($value) ? trim($value) : $value;
  }

}
