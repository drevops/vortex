<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "provision_type" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class ProvisionType extends AbstractHandler {

  const DATABASE = 'database';

  const PROFILE = 'profile';

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $provision_type = is_string($value) ? $value : '';

    Env::writeValueDotenv('VORTEX_PROVISION_TYPE', $provision_type, $context->directory . '/.env');

    if ($provision_type === 'profile') {
      File::remove($context->directory . '/scripts/sanitize.sql');
      File::removeTokenAsync('!PROVISION_TYPE_PROFILE');
    }
    else {
      File::removeTokenAsync('PROVISION_TYPE_PROFILE');
    }
  }

}
