<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;
use DrevOps\VortexCli\Utils\Env;

/**
 * First processor: carries existing .env values into the processed .env.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Dotenv extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $env_file = $context->directory . '/.env';

    if (is_readable($env_file)) {
      $variables = Env::parseDotenv($env_file);
      foreach ($variables as $name => $variable_value) {
        Env::writeValueDotenv($name, $variable_value, $env_file);
      }
    }
  }

}
