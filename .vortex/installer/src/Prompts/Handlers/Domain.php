<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class Domain extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    $origin = Env::getFromDotenv('DRUPAL_STAGE_FILE_PROXY_ORIGIN', $this->dstDir);

    if ($origin) {
      return Converter::domain($origin);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    File::replaceContentInDir($this->tmpDir, 'your-site-domain.example', (string) $this->response);
  }

}
