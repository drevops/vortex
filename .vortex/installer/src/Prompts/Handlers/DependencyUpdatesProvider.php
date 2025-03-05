<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Util;
use DrevOps\Installer\Utils\File;

class DependencyUpdatesProvider extends AbstractHandler {

  const NONE = 'none';

  const RENOVATEBOT_CI = 'renovatebot_ci';

  const RENOVATEBOT_APP = 'renovatebot_app';


  public function discover(): null|string|bool|iterable {
    if (!$this->isInstalled()) {
      return NULL;
    }

    return is_readable($this->dstDir . '/renovate.json');
  }

  public function process(): void {
    if ($this->response === 'renovatebot_ci') {
      File::removeTokenWithContent('!RENOVATEBOT_CI', $this->tmpDir);
      File::removeTokenWithContent('RENOVATEBOT_APP', $this->tmpDir);
    }
    elseif ($this->response === 'renovatebot_app') {
      File::removeTokenWithContent('!RENOVATEBOT_APP', $this->tmpDir);
      File::removeTokenWithContent('RENOVATEBOT_CI', $this->tmpDir);
    }
    else {
      File::removeTokenWithContent('RENOVATEBOT_APP', $this->tmpDir);
      File::removeTokenWithContent('RENOVATEBOT_CI', $this->tmpDir);
      File::removeTokenWithContent('RENOVATEBOT', $this->tmpDir);
      @unlink($this->tmpDir . '/renovate.json');
    }
  }

}
