<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class PreserveDocsOnboarding extends AbstractHandler {


  public function discover(): ?string {
    if ($this->isInstalled()) {
      $file = $this->config->getDst() . '/docs/onboarding.md';

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
