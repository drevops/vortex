<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\AbstractHandler as TuiAbstractHandler;
use DrevOps\Tui\Handler\Context;

/**
 * Base handler for the CLI: TUI collection defaults plus a no-op process().
 *
 * Inherits the no-op collection methods (default/discover/validate/transform)
 * from the TUI base and adds a no-op process(), so a concrete handler overrides
 * only the phases it needs.
 *
 * @package DrevOps\VortexCli\Handler
 */
abstract class AbstractHandler extends TuiAbstractHandler implements ProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
  }

}
