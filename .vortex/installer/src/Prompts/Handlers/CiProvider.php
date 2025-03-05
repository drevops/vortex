<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Util;
use DrevOps\Installer\Utils\File;

class CiProvider extends AbstractHandler {

  const NONE = 'none';

  const GHA = 'gha';

  const CIRCLECI = 'circleci';


  public static function id(): string {
    return 'ci_provider';
  }

  public function discover(): null|string|bool|iterable {
    if (is_readable($this->config->getDst() . '/.github/workflows/build-test-deploy.yml')) {
      return 'GitHub Actions';
    }

    if (is_readable($this->config->getDst() . '/.circleci/config.yml')) {
      return 'CircleCI';
    }

    return $this->isInstalled() ? 'none' : NULL;
  }

  public function process(): void {
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
      @unlink($this->tmpDir . '/.github/workflows/build-test-deploy.yml');
      File::removeTokenWithContent('CI_PROVIDER_GHA', $this->tmpDir);
    }

    if ($remove_circleci) {
      File::rmdirRecursive($this->tmpDir . '/.circleci');
      @unlink($this->tmpDir . '/tests/phpunit/CircleCiConfigTest.php');
      File::removeTokenWithContent('CI_PROVIDER_CIRCLECI', $this->tmpDir);
    }

    if ($remove_gha && $remove_circleci) {
      @unlink($this->tmpDir . '/docs/ci.md');
      File::removeTokenWithContent('CI_PROVIDER_ANY', $this->tmpDir);
    }
    else {
      File::removeTokenWithContent('!CI_PROVIDER_ANY', $this->tmpDir);
    }
  }

}
