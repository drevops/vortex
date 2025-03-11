<?php

declare(strict_types=1);

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
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    if (!empty($this->response)) {
      File::removeTokenInDir($this->tmpDir, '!DOCS_ONBOARDING');
    }
    else {
      @unlink($this->tmpDir . '/docs/onboarding.md');
      File::removeTokenInDir($this->tmpDir, 'DOCS_ONBOARDING');
    }
  }

}
