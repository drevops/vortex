<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Util;
use DrevOps\Installer\Utils\File;

class DocsOnboardingHandler extends AbstractHandler {

  public function discover() {
    if ($this->isInstalled()) {
      $file = $this->config->getDstDir() . '/docs/onboarding.md';

      return is_readable($file);
    }

    return FALSE;
  }

  public function process(array $responses, string $dir): void {
    if ($responses[PromptFields::DOCS_ONBOARDING]) {
      File::removeTokenWithContent('!DOCS_ONBOARDING', $dir);
    }
    else {
      @unlink($dir . '/docs/onboarding.md');
      File::removeTokenWithContent('DOCS_ONBOARDING', $dir);
    }
  }

}
