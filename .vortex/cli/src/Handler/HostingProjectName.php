<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;

/**
 * Handler for the "hosting_project_name" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class HostingProjectName extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && Validate::isPhpPackageName($value) ? NULL : 'Please enter a valid machine name: only lowercase letters, numbers, hyphens and underscores are allowed.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
    return is_string($value) ? trim($value) : $value;
  }

}
