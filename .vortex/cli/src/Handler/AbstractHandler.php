<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;

/**
 * Base processor with a no-op process().
 *
 * A concrete handler overrides process() with its side effects and may also
 * declare public static validate()/transform() methods, which the TUI engine
 * discovers by field id as the field's reusable behaviour.
 *
 * @package DrevOps\VortexCli\Handler
 */
abstract class AbstractHandler implements ProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
  }

}
