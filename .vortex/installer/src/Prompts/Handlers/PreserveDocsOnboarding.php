<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class PreserveDocsOnboarding extends AbstractHandler {


  public function discover(): null|string|bool|array {
    if ($this->isInstalled()) {
      $file = $this->dstDir . '/docs/onboarding.md';

      return is_readable($file);
    }

    return FALSE;
  }

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
