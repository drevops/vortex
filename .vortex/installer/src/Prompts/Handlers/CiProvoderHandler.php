<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Util;
use DrevOps\Installer\Utils\File;

class CiProvoderHandler extends AbstractHandler {

  public function discover() {
    if (is_readable($this->config->getDstDir() . '/.github/workflows/build-test-deploy.yml')) {
      return 'GitHub Actions';
    }

    if (is_readable($this->config->getDstDir() . '/.circleci/config.yml')) {
      return 'CircleCI';
    }

    return $this->isInstalled() ? 'none' : NULL;
  }

  public function process(array $responses, string $dir): void {
    $type = $this->getAnswer('ci_provider');

    $remove_gha = FALSE;
    $remove_circleci = FALSE;

    switch ($type) {
      case 'CircleCI':
        $remove_gha = TRUE;
        break;

      case 'GitHub Actions':
        $remove_circleci = TRUE;
        break;

      default:
        $remove_circleci = TRUE;
        $remove_gha = TRUE;
    }

    if ($remove_gha) {
      @unlink($dir . '/.github/workflows/build-test-deploy.yml');
      File::removeTokenWithContent('CI_PROVIDER_GHA', $dir);
    }

    if ($remove_circleci) {
      File::rmdirRecursive($dir . '/.circleci');
      @unlink($dir . '/tests/phpunit/CircleCiConfigTest.php');
      File::removeTokenWithContent('CI_PROVIDER_CIRCLECI', $dir);
    }

    if ($remove_gha && $remove_circleci) {
      @unlink($dir . '/docs/ci.md');
      File::removeTokenWithContent('CI_PROVIDER_ANY', $dir);
    }
    else {
      File::removeTokenWithContent('!CI_PROVIDER_ANY', $dir);
    }
  }

}
