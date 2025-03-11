<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class DatabaseDownloadSource extends AbstractHandler {

  const URL = 'url';

  const FTP = 'ftp';

  const ACQUIA = 'acquia';

  const LAGOON = 'lagoon';

  const CONTAINER_REGISTRY = 'container_registry';

  const NONE = 'none';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return Env::getFromDotenv('VORTEX_DB_DOWNLOAD_SOURCE', $this->dstDir);
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    $type = $this->response;

    File::replaceContent($this->tmpDir . '/.env', '/VORTEX_DB_DOWNLOAD_SOURCE=.*/', 'VORTEX_DB_DOWNLOAD_SOURCE=' . $type);

    $types = [
      DatabaseDownloadSource::URL,
      DatabaseDownloadSource::FTP,
      DatabaseDownloadSource::ACQUIA,
      DatabaseDownloadSource::LAGOON,
      DatabaseDownloadSource::CONTAINER_REGISTRY,
    ];

    foreach ($types as $t) {
      $token = 'DB_DOWNLOAD_SOURCE_' . strtoupper($t);
      if ($t === $type) {
        File::removeTokenInDir($this->tmpDir, '!' . $token);
      }
      else {
        File::removeTokenInDir($this->tmpDir, $token);
      }
    }
  }

}
