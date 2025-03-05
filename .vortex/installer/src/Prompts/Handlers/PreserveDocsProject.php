<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class PreserveDocsProject extends AbstractHandler {

  public function discover(): null|string|bool|array {
    return $this->isInstalled() && is_dir($this->dstDir . '/docs');
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
