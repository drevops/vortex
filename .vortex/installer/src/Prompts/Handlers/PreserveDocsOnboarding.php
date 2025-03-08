<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class PreserveDocsOnboarding extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return $this->isInstalled() ? file_exists($this->dstDir . '/docs/onboarding.md') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if ($this->response) {
      File::removeTokenWithContent('!DOCS_ONBOARDING', $this->tmpDir);
    }
    else {
      @unlink($this->tmpDir . '/docs/onboarding.md');
      File::removeTokenWithContent('DOCS_ONBOARDING', $this->tmpDir);
    }
  }

}
