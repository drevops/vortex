<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class DependencyUpdatesProvider extends AbstractHandler {

  const NONE = 'none';

  const RENOVATEBOT_CI = 'renovatebot_ci';

  const RENOVATEBOT_APP = 'renovatebot_app';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    if (!is_readable($this->dstDir . '/renovate.json')) {
      return self::NONE;
    }

    if (file_exists($this->dstDir . '.github/workflows/renovate.yml')) {
      return self::RENOVATEBOT_CI;
    }

    if (File::contains('renovatebot_schedule', $this->dstDir . '.circleci/config.yml')) {
      return self::RENOVATEBOT_CI;
    }

    return self::RENOVATEBOT_APP;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if ($this->response === self::RENOVATEBOT_CI) {
      File::removeTokenWithContent('!RENOVATEBOT_CI', $this->tmpDir);
      File::removeTokenWithContent('RENOVATEBOT_APP', $this->tmpDir);
    }
    elseif ($this->response === self::RENOVATEBOT_APP) {
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
