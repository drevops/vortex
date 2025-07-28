<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;

class Dotenv extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return '.env processing';
  }

  public function discover(): null|string|bool|array {

    return NULL;
  }

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
