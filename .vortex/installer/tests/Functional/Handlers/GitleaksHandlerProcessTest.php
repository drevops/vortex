<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Gitleaks;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Gitleaks::class)]
class GitleaksHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'gitleaks_enabled' => [
      static::cw(fn($test): true => $test->prompts[Gitleaks::id()] = TRUE),
    ];
    yield 'gitleaks_disabled' => [
      static::cw(fn($test): false => $test->prompts[Gitleaks::id()] = FALSE),
    ];
  }

}
