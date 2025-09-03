<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

class CiProvider extends AbstractHandler {

  const NONE = 'none';

  const GITHUB_ACTIONS = 'gha';

  const CIRCLECI = 'circleci';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Continuous Integration provider';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇ to select the CI provider.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    $options = [
      self::GITHUB_ACTIONS => 'GitHub Actions',
      self::CIRCLECI => 'CircleCI',
      self::NONE => 'None',
    ];

    if (isset($responses[CodeProvider::id()]) && $responses[CodeProvider::id()] !== CodeProvider::GITHUB) {
      unset($options[self::GITHUB_ACTIONS]);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::GITHUB_ACTIONS;
  }

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
    $v = $this->getResponseAsString();

    $remove_gha = FALSE;
    $remove_circleci = FALSE;

    switch ($v) {
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
      @unlink($this->tmpDir . '/' . $this->webroot . '/sites/default/includes/providers/settings.gha.php');
      File::removeTokenAsync('CI_PROVIDER_GHA');
      File::removeTokenAsync('SETTINGS_PROVIDER_GHA');
    }

    if ($remove_circleci) {
      File::rmdir($this->tmpDir . '/.circleci');
      @unlink($this->tmpDir . '/' . $this->webroot . '/sites/default/includes/providers/settings.circleci.php');
      @unlink($this->tmpDir . '/tests/phpunit/CircleCiConfigTest.php');
      File::removeTokenAsync('CI_PROVIDER_CIRCLECI');
      File::removeTokenAsync('SETTINGS_PROVIDER_CIRCLECI');
    }

    if ($remove_gha && $remove_circleci) {
      @unlink($this->tmpDir . '/docs/ci.md');
      File::removeTokenAsync('CI_PROVIDER_ANY');
    }
    else {
      File::removeTokenAsync('!CI_PROVIDER_ANY');
    }
  }

}
