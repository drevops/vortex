<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Converter;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "machine_name" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class MachineName extends AbstractHandler implements FieldInterface {

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
    return is_string($value) && Validate::isMachineName($value) ? NULL : 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.';
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
    $machine_name = is_string($value) ? $value : '';

    File::replaceContentAsync([
      'your_site' => $machine_name,
      'your-site' => Converter::kebab($machine_name),
      'YourSite' => Converter::pascal($machine_name),
    ]);

    File::renameInDir($context->directory, 'your_site', $machine_name);
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->text('machine_name', 'Site machine name')
      ->description('We will use this name for the project directory and in the code.')
      ->required()
      ->derive(new Derive('{{name}}', 'machine'))
      ->weight(360);
  }

}
