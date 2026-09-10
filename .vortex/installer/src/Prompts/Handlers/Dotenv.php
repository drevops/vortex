<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class Dotenv extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public static function processWeight(): int {
    return 10;
  }

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

    if (File::exists($this->destinationDir . '/.env')) {
      $variables = Env::parseDotenv($this->destinationDir . '/.env');
      foreach ($variables as $name => $value) {
        Env::writeValueDotenv($name, $value, $t . '/.env');
      }
    }
  }

}
