<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class FrontendBuildHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'frontend_build_container' => [
      self::cw(fn($test): true => $test->prompts['frontend_build'] = TRUE),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FRONTEND_BUILD_SKIP=0');
      }),
    ];

    yield 'frontend_build_skip' => [
      self::cw(fn($test): false => $test->prompts['frontend_build'] = FALSE),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FRONTEND_BUILD_SKIP=1');
      }),
    ];
  }

}
