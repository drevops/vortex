<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\FrontendBuild;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FrontendBuild::class)]
class FrontendBuildHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'frontend_build_container' => [
      static::cw(fn($test): true => $test->prompts[FrontendBuild::id()] = TRUE),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FRONTEND_BUILD_SKIP=0');
      }),
    ];

    yield 'frontend_build_skip' => [
      static::cw(fn($test): false => $test->prompts[FrontendBuild::id()] = FALSE),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FRONTEND_BUILD_SKIP=1');
      }),
    ];
  }

}
