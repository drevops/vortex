<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;
use DrevOps\VortexCli\Utils\Converter;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "machine_name" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class MachineName extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && Validate::isMachineName($value) ? NULL : 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
    return is_string($value) ? trim($value) : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $machine_name = is_string($value) ? $value : '';

    File::replaceContentAsync([
      'your_site' => $machine_name,
      'your-site' => Converter::kebab($machine_name),
      'YourSite' => Converter::pascal($machine_name),
    ]);

    File::renameInDir($context->directory, 'your_site', $machine_name);
  }

}
