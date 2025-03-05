<?php

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
    $image = $this->response;

    File::fileReplaceContent('/VORTEX_DB_IMAGE=.*/', 'VORTEX_DB_IMAGE=' . $image, $this->tmpDir . '/.env');

    if ($image !== '' && $image !== '0') {
      File::removeTokenWithContent('!VORTEX_DB_IMAGE', $this->tmpDir);
    }
    else {
      File::removeTokenWithContent('VORTEX_DB_IMAGE', $this->tmpDir);
    }
  }
}
