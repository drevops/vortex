<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(DependencyUpdatesProvider::class)]
class DependencyUpdatesProviderPromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'dependency updates provider - prompt' => [
        [DependencyUpdatesProvider::id() => Key::ENTER],
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI] + $expected_defaults,
      ],

      'dependency updates provider - discovery - renovate self-hosted - gha' => [
        [],
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
          File::dump(static::$sut . '/.github/workflows/update-dependencies.yml');
        },
      ],

      'dependency updates provider - discovery - renovate self-hosted - circleci' => [
        [],
        [
          CiProvider::id() => CiProvider::CIRCLECI,
          DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI,
        ] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
          File::dump(static::$sut . '/.circleci/config.yml', 'update-dependencies');
        },
      ],

      'dependency updates provider - discovery - renovate app' => [
        [],
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_APP] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/renovate.json');
        },
      ],

      'dependency updates provider - discovery - none' => [
        [],
        [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::NONE] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],

      'dependency updates provider - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No renovate.json and not installed - should fall back to default.
        },
      ],
    ];
  }

}
