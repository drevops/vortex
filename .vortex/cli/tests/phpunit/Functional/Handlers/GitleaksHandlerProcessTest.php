<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class GitleaksHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'gitleaks_enabled' => [
      self::cw(fn($test): true => $test->prompts['gitleaks'] = TRUE),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileExists(self::$sut . '/.gitleaks.toml');
      }),
    ];
    yield 'gitleaks_disabled' => [
      self::cw(fn($test): false => $test->prompts['gitleaks'] = FALSE),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(self::$sut . '/.gitleaks.toml');
      }),
    ];
  }

}
