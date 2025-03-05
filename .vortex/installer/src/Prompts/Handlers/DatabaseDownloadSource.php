<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class DatabaseDownloadSource extends AbstractHandler {

  const URL = 'url';

  const FTP = 'ftp';

  const ACQUIA = 'acquia';

  const LAGOON = 'lagoon';

  const CONTAINER_REGISTRY= 'container_registry';

  public static function id(): string {
    return 'database_download_source';
  }

  public function discover(): null|string|bool|iterable {
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
