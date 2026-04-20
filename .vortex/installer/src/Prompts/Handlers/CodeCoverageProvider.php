<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

class CodeCoverageProvider extends AbstractHandler {

  const NONE = 'none';

  const CODECOV = 'codecov';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Code coverage provider';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇ to select the code coverage provider.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::CODECOV => 'Codecov',
      self::NONE => 'None',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::NONE;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    $gha = $this->dstDir . '/.github/workflows/build-test-deploy.yml';
    if (is_readable($gha) && File::contains($gha, 'codecov/codecov-action')) {
      return self::CODECOV;
    }

    $circle = $this->dstDir . '/.circleci/config.yml';
    if (is_readable($circle) && File::contains($circle, 'codecov -Z -s')) {
      return self::CODECOV;
    }

    return self::NONE;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();

    if ($v === self::CODECOV) {
      File::removeTokenAsync('!CODE_COVERAGE_PROVIDER_CODECOV');
    }
    else {
      File::removeTokenAsync('CODE_COVERAGE_PROVIDER_CODECOV');
    }
  }

}
