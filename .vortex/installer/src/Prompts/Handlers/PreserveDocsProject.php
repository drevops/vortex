<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class PreserveDocsProject extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return $this->isInstalled() ? file_exists($this->dstDir . '/docs/README.md') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    if (!empty($v)) {
      File::removeTokenAsync('!DOCS_PROJECT');
    }
    else {
      File::rmdir($this->tmpDir . '/docs');
      File::removeTokenAsync('DOCS_PROJECT');
    }
  }

}
