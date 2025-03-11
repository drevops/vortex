<?php

declare(strict_types=1);

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

    if (file_exists($this->dstDir . '/.github/workflows/deps-updates.yml')) {
      return self::RENOVATEBOT_CI;
    }

    if (File::contains($this->dstDir . '/.circleci/config.yml', 'deps-updates')) {
      return self::RENOVATEBOT_CI;
    }

    return self::RENOVATEBOT_APP;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    $v = $this->response;
    $t = $this->tmpDir;

    if ($v === self::RENOVATEBOT_CI) {
      File::removeTokenInDir($t, '!DEPS_UPDATE_PROVIDER_CI');
      File::removeTokenInDir($t, 'DEPS_UPDATE_PROVIDER_APP');
    }
    elseif ($v === self::RENOVATEBOT_APP) {
      File::removeTokenInDir($t, '!DEPS_UPDATE_PROVIDER_APP');
      File::removeTokenInDir($t, 'DEPS_UPDATE_PROVIDER_CI');
      @unlink($t . '/.github/workflows/deps-updates.yml');
    }
    else {
      File::removeTokenInDir($t, 'DEPS_UPDATE_PROVIDER_APP');
      File::removeTokenInDir($t, 'DEPS_UPDATE_PROVIDER_CI');
      File::removeTokenInDir($t, 'DEPS_UPDATE_PROVIDER');
      @unlink($t . '/renovate.json');
    }
  }

}
