<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CodeProvider;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CodeProvider::class)]
class CodeProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'code_provider_github' => [
      static::cw(fn($test): string => $test->prompts[CodeProvider::id()] = CodeProvider::GITHUB),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/.github/PULL_REQUEST_TEMPLATE.dist.md');
          $test->assertFileContainsString(static::$sut . '/.github/PULL_REQUEST_TEMPLATE.md', 'Checklist before requesting a review');
      }),
    ];
    yield 'code_provider_other' => [
      static::cw(fn($test): string => $test->prompts[CodeProvider::id()] = CodeProvider::OTHER),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertDirectoryDoesNotExist(static::$sut . '/.github');
      }),
    ];
  }

}
