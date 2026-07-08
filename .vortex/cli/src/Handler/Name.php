<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Converter;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "name" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Name extends AbstractHandler implements FieldInterface {

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
    return is_string($value) && trim($value) !== '' ? NULL : 'The site name is required.';
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
    File::replaceContentAsync('YOURSITE', is_string($value) ? $value : '');
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->text('name', 'Site name')->description('We will use this name in the project and documentation.')->required()->default(fn (Context $c): string => Converter::label(basename($c->directory)))->weight(380);
  }

}
