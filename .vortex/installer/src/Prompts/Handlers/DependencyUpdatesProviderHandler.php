<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Util;
use DrevOps\Installer\Utils\File;

class DependencyUpdatesProviderHandler extends AbstractHandler {

  public function discover(): ?string {
    if (!$this->isInstalled()) {
      return NULL;
    }

    return is_readable($this->config->getDst() . '/renovate.json') ? self::ANSWER_YES : self::ANSWER_NO;
  }

  public function process(): void {
    if ($responses[PromptFields::DEPENDENCY_UPDATES_PROVIDER] === 'renovatebot_ci') {
      File::removeTokenWithContent('!RENOVATEBOT_CI', $dir);
      File::removeTokenWithContent('RENOVATEBOT_APP', $dir);
    }
    elseif ($responses[PromptFields::DEPENDENCY_UPDATES_PROVIDER] === 'renovatebot_app') {
      File::removeTokenWithContent('!RENOVATEBOT_APP', $dir);
      File::removeTokenWithContent('RENOVATEBOT_CI', $dir);
    }
    else {
      File::removeTokenWithContent('RENOVATEBOT_APP', $dir);
      File::removeTokenWithContent('RENOVATEBOT_CI', $dir);
      File::removeTokenWithContent('RENOVATEBOT', $dir);
      @unlink($dir . '/renovate.json');
    }
  }

}
