<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class DatabaseDownloadSourceHandler extends AbstractHandler {

  public function discover() {
    return Env::getFromDstDotenv('VORTEX_DB_DOWNLOAD_SOURCE');
  }

  public function process(array $responses, string $dir): void {
    $type = $this->getAnswer('database_download_source');
    File::fileReplaceContent('/VORTEX_DB_DOWNLOAD_SOURCE=.*/', 'VORTEX_DB_DOWNLOAD_SOURCE=' . $type, $dir . '/.env');

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
        File::removeTokenWithContent('!' . $token, $dir);
      }
      else {
        File::removeTokenWithContent($token, $dir);
      }
    }
  }
}
