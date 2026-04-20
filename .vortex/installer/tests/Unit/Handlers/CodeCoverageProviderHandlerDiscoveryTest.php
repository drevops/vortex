<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeCoverageProvider;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(CodeCoverageProvider::class)]
class CodeCoverageProviderHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'code coverage provider - prompt' => [
      [CodeCoverageProvider::id() => Key::ENTER],
      [CodeCoverageProvider::id() => CodeCoverageProvider::NONE] + $expected_defaults,
    ];
    yield 'code coverage provider - discovery - codecov - gha' => [
      [],
      [
        CiProvider::id() => CiProvider::GITHUB_ACTIONS,
        CodeCoverageProvider::id() => CodeCoverageProvider::CODECOV,
      ] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/.github/workflows/build-test-deploy.yml', 'codecov/codecov-action');
      },
    ];
    yield 'code coverage provider - discovery - codecov - circleci' => [
      [],
      [
        CiProvider::id() => CiProvider::CIRCLECI,
        CodeCoverageProvider::id() => CodeCoverageProvider::CODECOV,
      ] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/.circleci/config.yml', 'codecov -Z -s');
      },
    ];
    yield 'code coverage provider - discovery - none' => [
      [],
      [CodeCoverageProvider::id() => CodeCoverageProvider::NONE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
      },
    ];
    yield 'code coverage provider - discovery - non-Vortex' => [
      [],
      [CodeCoverageProvider::id() => CodeCoverageProvider::NONE] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        File::dump(static::$sut . '/.github/workflows/build-test-deploy.yml', 'codecov/codecov-action');
      },
    ];
    yield 'code coverage provider - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        // No CI config and not installed - fall back to default.
      },
    ];
  }

}
