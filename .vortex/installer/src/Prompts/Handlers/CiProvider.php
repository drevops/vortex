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
    $t = $this->tmpDir;

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
      File::remove($t . '/.github/workflows/build-test-deploy.yml');
      File::remove($t . '/' . $this->webroot . '/sites/default/includes/providers/settings.gha.php');
      File::removeTokenAsync('CI_PROVIDER_GHA');
      File::removeTokenAsync('SETTINGS_PROVIDER_GHA');
    }

    if ($remove_circleci) {
      File::remove($t . '/.circleci');
      File::remove($t . '/' . $this->webroot . '/sites/default/includes/providers/settings.circleci.php');
      File::remove($t . '/tests/phpunit/CircleCiConfigTest.php');
      File::removeTokenAsync('CI_PROVIDER_CIRCLECI');
      File::removeTokenAsync('SETTINGS_PROVIDER_CIRCLECI');
    }

    if ($remove_gha && $remove_circleci) {
      File::remove($t . '/docs/ci.md');
      File::removeTokenAsync('CI_PROVIDER_ANY');
    }
    else {
      File::removeTokenAsync('!CI_PROVIDER_ANY');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postInstall(): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function postBuild(string $result): ?string {
    if ($this->isInstalled()) {
      return NULL;
    }

    $v = $this->getResponseAsString();

    if ($v === self::GITHUB_ACTIONS) {
      return 'Setup GitHub Actions:' . PHP_EOL
        . '  https://www.vortextemplate.com/docs/continuous-integration/github-actions#onboarding' . PHP_EOL
        . PHP_EOL;
    }

    if ($v === self::CIRCLECI) {
      return 'Setup CircleCI:' . PHP_EOL
        . '  https://www.vortextemplate.com/docs/continuous-integration/circleci#onboarding' . PHP_EOL
        . PHP_EOL;
    }

    return NULL;
  }

}
