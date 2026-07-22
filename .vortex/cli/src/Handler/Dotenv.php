<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\VortexCli\Utils\Env;

/**
 * Writes the collected answers to the ".env" file.
 */
class Dotenv extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '.env processing';
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $t = $this->tmpDir;

    if (is_readable($this->dstDir . '/.env')) {
      $variables = Env::parseDotenv($this->dstDir . '/.env');
      foreach ($variables as $name => $value) {
        Env::writeValueDotenv($name, $value, $t . '/.env');
      }
    }

  }

}
