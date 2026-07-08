<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;

/**
 * Handler for the "frontend_build" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class FrontendBuild extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if (is_bool($value)) {
      Env::writeValueDotenv('VORTEX_FRONTEND_BUILD_SKIP', $value ? '0' : '1', $context->directory . '/.env');
    }
  }

}
