<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;

/**
 * Handler for the "domain" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Domain extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && Validate::isDomain($value) ? NULL : 'Please enter a valid domain name.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
    return is_string($value) ? Validate::domain($value) : $value;
  }

}
