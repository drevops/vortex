<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\ProvisionType;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProvisionType::class)]
class ProvisionTypeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'provision, database' => [
        static::cw(fn() => Env::put(ProvisionType::envName(), ProvisionType::DATABASE)),
      ],

      'provision, database, lagoon' => [
        static::cw(function (): void {
          Env::put(ProvisionType::envName(), ProvisionType::DATABASE);
          Env::put(HostingProvider::envName(), HostingProvider::LAGOON);
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
      ],

      'provision, profile' => [
        static::cw(function (): void {
          Env::put(ProvisionType::envName(), ProvisionType::PROFILE);
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
      ],
    ];
  }

}
