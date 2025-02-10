<?php

namespace DrevOps\Installer\Discovery;

use DrevOps\Installer\Util;

class CiProvoderDiscovery extends AbstractDiscovery {

  public function discover() {
    if (is_readable($this->config->getDstDir() . '/.github/workflows/build-test-deploy.yml')) {
      return 'GitHub Actions';
    }

    if (is_readable($this->config->getDstDir() . '/.circleci/config.yml')) {
      return 'CircleCI';
    }

    return $this->isInstalled() ? 'none' : NULL;
  }

}
