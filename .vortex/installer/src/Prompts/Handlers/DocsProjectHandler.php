<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Util;
use DrevOps\Installer\Utils\File;

class DocsProjectHandler extends AbstractHandler {

  public function discover(): ?string {
    return $this->isInstalled() && is_dir($this->config->getDst() . '/docs');
  }

  public function process(): void {
    if ($responses[PromptFields::DOCS_ONBOARDING]) {
      File::removeTokenWithContent('!DOCS_PROJECT', $dir);
    }
    else {
      File::rmdirRecursive($dir . '/docs');
      File::removeTokenWithContent('DOCS_PROJECT', $dir);
    }
  }

}
