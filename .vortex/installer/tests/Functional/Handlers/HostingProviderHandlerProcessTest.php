<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HostingProvider::class)]
class HostingProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'hosting_acquia' => [
      static::cw(function ($test): void {
          $test->prompts[HostingProvider::id()] = HostingProvider::ACQUIA;
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      // Cannot assert for the full absence of 'lagoon' since we use Lagoon
      // images for local and CI even with Acquia.
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('lagoon_')),
    ];
    yield 'hosting_lagoon' => [
      static::cw(function ($test): void {
          $test->prompts[HostingProvider::id()] = HostingProvider::LAGOON;
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('acquia')),
    ];
  }

}
