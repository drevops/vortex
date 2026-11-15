<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Lighthouse;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Lighthouse::class)]
class LighthouseHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'lighthouse_enabled' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): true => $test->prompts[Lighthouse::id()] = TRUE),
    ];
    yield 'lighthouse_enabled_circleci' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
        $test->prompts[Lighthouse::id()] = TRUE;
        $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
    ];
    yield 'lighthouse_disabled' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): false => $test->prompts[Lighthouse::id()] = FALSE),
    ];
  }

}
