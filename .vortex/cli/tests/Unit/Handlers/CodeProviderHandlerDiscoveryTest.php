<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\CiProvider;
use DrevOps\VortexCli\Prompts\Handlers\CodeProvider;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\Git;
use DrevOps\VortexCli\Tests\Support\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CodeProvider::class)]
class CodeProviderHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'code repo - prompt' => [
      [CodeProvider::id() => Key::ENTER],
      [CodeProvider::id() => CodeProvider::GITHUB] + $expected_defaults,
    ];
    yield 'code repo - prompt - other' => [
      [CodeProvider::id() => Key::DOWN . Key::ENTER],
      [
        CodeProvider::id() => CodeProvider::OTHER,
        CiProvider::id() => CiProvider::CIRCLECI,
      ] + $expected_defaults,
    ];
    yield 'code repo - discovery' => [
      [],
      [CodeProvider::id() => CodeProvider::GITHUB] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        File::dump(static::$sut . '/.github/workflows/ci.yml');
      },
    ];
    yield 'code repo - discovery - other' => [
      [],
      [CodeProvider::id() => CodeProvider::OTHER] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        Git::init(static::$sut);
      },
    ];
    yield 'code repo - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        // No .github directory and no .git directory - fall back to default.
      },
    ];
  }

}
