<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class DatabaseImage extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return Env::getFromDotenv('VORTEX_DB_IMAGE', $this->dstDir);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if (!empty($this->response)) {
      $v = $this->getResponseAsString();

      File::replaceContentInFile(
        $this->tmpDir . '/.env', '/# VORTEX_DB_IMAGE=.*/',
        'VORTEX_DB_IMAGE=' . $v
      );
    }
  }

}
