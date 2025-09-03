<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(CiProvider::class)]
class CiProviderPromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'ci provider - prompt' => [
        [CiProvider::id() => Key::ENTER],
        [CiProvider::id() => CiProvider::GITHUB_ACTIONS] + $expected_defaults,
      ],

      'ci provider - discovery - gha' => [
        [],
        [CiProvider::id() => CiProvider::GITHUB_ACTIONS] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/build-test-deploy.yml');
        },
      ],

      'ci provider - discovery - circleci' => [
        [],
        [CiProvider::id() => CiProvider::CIRCLECI] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/.circleci/config.yml');
        },
      ],

      'ci provider - discovery - none' => [
        [],
        [CiProvider::id() => CiProvider::NONE] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],

      'ci provider - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No CI files and not installed - should fall back to default.
        },
      ],
    ];
  }

}
