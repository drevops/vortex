<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class PreserveDocsProject extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return $this->isInstalled() ? is_dir($this->dstDir . '/docs') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if ($this->response) {
      File::removeTokenWithContent('!DOCS_PROJECT', $this->dstDir);
    }
    else {
      File::rmdirRecursive($this->dstDir . '/docs');
      File::removeTokenWithContent('DOCS_PROJECT', $this->dstDir);
    }
  }

}
