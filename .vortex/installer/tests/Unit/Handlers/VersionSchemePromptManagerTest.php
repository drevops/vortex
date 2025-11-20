<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\VersionScheme;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(VersionScheme::class)]
class VersionSchemePromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'version scheme - prompt' => [
        [VersionScheme::id() => Key::ENTER],
        $expected_defaults,
      ],

      'version scheme - discovery - calver' => [
        [],
        [VersionScheme::id() => VersionScheme::CALVER] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('VORTEX_RELEASE_VERSION_SCHEME', VersionScheme::CALVER);
        },
      ],

      'version scheme - discovery - semver' => [
        [],
        [VersionScheme::id() => VersionScheme::SEMVER] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('VORTEX_RELEASE_VERSION_SCHEME', VersionScheme::SEMVER);
        },
      ],

      'version scheme - discovery - other' => [
        [],
        [VersionScheme::id() => VersionScheme::OTHER] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('VORTEX_RELEASE_VERSION_SCHEME', VersionScheme::OTHER);
        },
      ],

      'version scheme - discovery - missing .env' => [
        [],
        $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],
    ];
  }

}
