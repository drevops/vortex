<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeProvider;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Git;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CodeProvider::class)]
class CodeProviderHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'code repo - prompt' => [
        [CodeProvider::id() => Key::ENTER],
        [CodeProvider::id() => CodeProvider::GITHUB] + $expected_defaults,
      ],

      'code repo - prompt - other' => [
        [CodeProvider::id() => Key::DOWN . Key::ENTER],
        [
          CodeProvider::id() => CodeProvider::OTHER,
          CiProvider::id() => CiProvider::CIRCLECI,
        ] + $expected_defaults,
      ],

      'code repo - discovery' => [
        [],
        [CodeProvider::id() => CodeProvider::GITHUB] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          File::dump(static::$sut . '/.github/workflows/ci.yml');
        },
      ],

      'code repo - discovery - other' => [
        [],
        [CodeProvider::id() => CodeProvider::OTHER] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          Git::init(static::$sut);
        },
      ],

      'code repo - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          // No .github directory and no .git directory - fall back to default.
        },
      ],

    ];
  }

}
