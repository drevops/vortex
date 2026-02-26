<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CodeProvider;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CodeProvider::class)]
class CodeProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'code_provider_github' => [
        static::cw(fn() => Env::put(CodeProvider::envName(), CodeProvider::GITHUB)),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/.github/PULL_REQUEST_TEMPLATE.dist.md');
          $test->assertFileContainsString(static::$sut . '/.github/PULL_REQUEST_TEMPLATE.md', 'Checklist before requesting a review');
        }),
      ],

      'code_provider_other' => [
        static::cw(fn() => Env::put(CodeProvider::envName(), CodeProvider::OTHER)),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertDirectoryDoesNotExist(static::$sut . '/.github');
        }),
      ],
    ];
  }

}
