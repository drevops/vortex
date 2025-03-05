<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class DatabaseDownloadSourceHandler extends AbstractHandler {

  public function discover(): ?string {
    return Env::getFromDotenv('VORTEX_DB_DOWNLOAD_SOURCE', $this->dstDir);
  }

  public function process(): void {
    $type = $this->response;
    File::fileReplaceContent('/VORTEX_DB_DOWNLOAD_SOURCE=.*/', 'VORTEX_DB_DOWNLOAD_SOURCE=' . $type, $this->dstDir . '/.env');

    $types = [
      'curl',
      'ftp',
      'acquia',
      'lagoon',
      'container_registry',
      'none',
    ];

    foreach ($types as $t) {
      $token = 'VORTEX_DB_DOWNLOAD_SOURCE_' . strtoupper($t);
      if ($t === $type) {
        File::removeTokenWithContent('!' . $token, $this->tmpDir);
      }
      else {
        File::removeTokenWithContent($token, $this->tmpDir);
      }
    }
  }
}
