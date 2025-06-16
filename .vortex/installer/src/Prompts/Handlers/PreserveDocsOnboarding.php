<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

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
    $v = $this->getResponseAsString();

    if (!empty($v)) {
      File::removeTokenAsync('!DOCS_ONBOARDING');
    }
    else {
      @unlink($this->tmpDir . '/docs/onboarding.md');
      File::removeTokenAsync('DOCS_ONBOARDING');
    }
  }

}
