<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "timezone" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Timezone extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $timezone = is_string($value) ? $value : '';

    Env::writeValueDotenv('TZ', $timezone, $context->directory . '/.env');
    File::replaceContentInFile($context->directory . '/renovate.json', '/"timezone": "[A-Za-z0-9\/_\-+]+",/', sprintf('"timezone": "%s",', $timezone));
  }

}
