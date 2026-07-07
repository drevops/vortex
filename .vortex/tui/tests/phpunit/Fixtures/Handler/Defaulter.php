<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Fixtures\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\AbstractHandler;
use DrevOps\Tui\Handler\Context;

/**
 * A fixture handler that provides a dynamic default from the context.
 */
class Defaulter extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function default(Field $field, Context $context): mixed {
    return 'dynamic-' . $context->directory;
  }

}
