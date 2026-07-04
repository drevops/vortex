<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Fixtures\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;

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
