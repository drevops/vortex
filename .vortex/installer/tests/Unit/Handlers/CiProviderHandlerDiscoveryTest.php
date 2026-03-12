<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(CiProvider::class)]
class CiProviderHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'ci provider - prompt' => [
      [CiProvider::id() => Key::ENTER],
      [CiProvider::id() => CiProvider::GITHUB_ACTIONS] + $expected_defaults,
    ];
    yield 'ci provider - discovery - gha' => [
      [],
      [CiProvider::id() => CiProvider::GITHUB_ACTIONS] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/.github/workflows/build-test-deploy.yml');
      },
    ];
    yield 'ci provider - discovery - circleci' => [
      [],
      [CiProvider::id() => CiProvider::CIRCLECI] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/.circleci/config.yml');
      },
    ];
    yield 'ci provider - discovery - none' => [
      [],
      [CiProvider::id() => CiProvider::NONE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
      },
    ];
    yield 'ci provider - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        // No CI files and not installed - should fall back to default.
      },
    ];
  }

}
