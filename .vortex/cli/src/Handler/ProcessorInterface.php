<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\Tui\Handler\HandlerInterface;

/**
 * A field handler that also applies its answer as a side effect.
 *
 * Extends the TUI's collection contract with process(): the TUI collects
 * answers through HandlerInterface and knows nothing about applying them, while
 * the CLI applies them here - so one handler class still carries both a field's
 * collection behaviour and its processing.
 *
 * @package DrevOps\VortexCli\Handler
 */
interface ProcessorInterface extends HandlerInterface {

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
