<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HostingProvider::class)]
class HostingProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'hosting_acquia' => [
        static::cw(function (): void {
          Env::put(HostingProvider::envName(), HostingProvider::ACQUIA);
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
      ],

      'hosting_lagoon' => [
        static::cw(function (): void {
          Env::put(HostingProvider::envName(), HostingProvider::LAGOON);
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('acquia')),
      ],
    ];
  }

}
