<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

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

    if (file_exists($this->dstDir . '/.github/workflows/update-dependencies.yml')) {
      return self::RENOVATEBOT_CI;
    }

    if (File::contains($this->dstDir . '/.circleci/config.yml', 'update-dependencies')) {
      return self::RENOVATEBOT_CI;
    }

    return self::RENOVATEBOT_APP;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    if ($v === self::RENOVATEBOT_CI) {
      File::removeTokenAsync('!DEPS_UPDATE_PROVIDER_CI');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_APP');
    }
    elseif ($v === self::RENOVATEBOT_APP) {
      File::removeTokenAsync('!DEPS_UPDATE_PROVIDER_APP');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_CI');
      @unlink($t . '/.github/workflows/update-dependencies.yml');
    }
    else {
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_APP');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_CI');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER');
      @unlink($t . '/renovate.json');
    }
  }

}
