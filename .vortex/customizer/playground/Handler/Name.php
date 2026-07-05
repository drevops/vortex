<?php

declare(strict_types=1);

namespace Playground\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;

/**
 * Example handler for the "name" field.
 *
 * Auto-discovered by the engine from the field id ("name" -> Name). Shows the
 * four handler hooks: a dynamic default from the run context, validation,
 * a value transform, and a process() side effect.
 */
class Name extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function default(Field $field, Context $context): mixed {
    // Default the name to a title-cased version of the current directory.
    return ucwords(str_replace(['-', '_'], ' ', basename($context->directory)));
  }

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && trim($value) !== '' ? NULL : 'The package name is required.';
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
    // A real handler would write files under $context->directory here. The
    // playground just reports what it would do.
    fwrite(STDOUT, sprintf('  [process] would scaffold package "%s"' . PHP_EOL, is_string($value) ? $value : ''));
  }

}
