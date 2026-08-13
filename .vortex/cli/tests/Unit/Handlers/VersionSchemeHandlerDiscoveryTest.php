<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\VersionScheme;
use DrevOps\VortexCli\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use DrevOps\VortexCli\Tests\Support\Key;

#[CoversClass(VersionScheme::class)]
class VersionSchemeHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'version scheme - prompt' => [
      [VersionScheme::id() => Key::ENTER],
      $expected_defaults,
    ];
    yield 'version scheme - discovery - calver' => [
      [],
      [VersionScheme::id() => VersionScheme::CALVER] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('VORTEX_RELEASE_VERSION_SCHEME', VersionScheme::CALVER);
      },
    ];
    yield 'version scheme - discovery - semver' => [
      [],
      [VersionScheme::id() => VersionScheme::SEMVER] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('VORTEX_RELEASE_VERSION_SCHEME', VersionScheme::SEMVER);
      },
    ];
    yield 'version scheme - discovery - other' => [
      [],
      [VersionScheme::id() => VersionScheme::OTHER] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('VORTEX_RELEASE_VERSION_SCHEME', VersionScheme::OTHER);
      },
    ];
    yield 'version scheme - discovery - missing .env' => [
      [],
      $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
      },
    ];
  }

}
