<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\File;

class CiProvider extends AbstractHandler {

  const NONE = 'none';

  const GITHUB_ACTIONS = 'gha';

  const CIRCLECI = 'circleci';

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    if (is_readable($this->dstDir . '/.github/workflows/build-test-deploy.yml')) {
      return self::GITHUB_ACTIONS;
    }

    if (is_readable($this->dstDir . '/.circleci/config.yml')) {
      return self::CIRCLECI;
    }

    return self::NONE;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if (!is_scalar($this->response)) {
      throw new \RuntimeException('Invalid response type.');
    }

    $remove_gha = FALSE;
    $remove_circleci = FALSE;

    switch ($this->response) {
      case self::GITHUB_ACTIONS:
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
      File::removeTokenInDir($this->tmpDir, 'CI_PROVIDER_GHA');
    }

    if ($remove_circleci) {
      File::rmdir($this->tmpDir . '/.circleci');
      @unlink($this->tmpDir . '/tests/phpunit/CircleCiConfigTest.php');
      File::removeTokenInDir($this->tmpDir, 'CI_PROVIDER_CIRCLECI');
    }

    if ($remove_gha && $remove_circleci) {
      @unlink($this->tmpDir . '/docs/ci.md');
      File::removeTokenInDir($this->tmpDir, 'CI_PROVIDER_ANY');
    }
    else {
      File::removeTokenInDir($this->tmpDir, '!CI_PROVIDER_ANY');
    }
  }

}
