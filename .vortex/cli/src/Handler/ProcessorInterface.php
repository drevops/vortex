<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;

/**
 * A per-field processor applying a collected answer as a side effect.
 *
 * The TUI collects answers and knows nothing about applying them; the CLI
 * applies them here, resolving each processor class by its field id.
 *
 * @package DrevOps\VortexCli\Handler
 */
interface ProcessorInterface {

  /**
   * Apply the collected answer (project-specific side effects).
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field being processed.
   * @param mixed $value
   *   The final value.
   * @param \DrevOps\Tui\Handler\Context $context
   *   The run context.
   */
  public function process(Field $field, mixed $value, Context $context): void;

}
