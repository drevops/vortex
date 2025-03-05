<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class CiProvider extends AbstractHandler {

  const NONE = 'none';

  const GHA = 'gha';

  const CIRCLECI = 'circleci';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (is_readable($this->dstDir . '/.github/workflows/build-test-deploy.yml')) {
      return self::GHA;
    }

    if (is_readable($this->dstDir . '/.circleci/config.yml')) {
      return self::CIRCLECI;
    }

    return $this->isInstalled() ? self::NONE : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $remove_gha = FALSE;
    $remove_circleci = FALSE;

    switch ($this->response) {
      case self::GHA:
        $remove_circleci = TRUE;
        break;

      case self::CIRCLECI:
        $remove_gha = TRUE;
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
