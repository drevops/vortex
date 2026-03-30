<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\ProvisionType;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProvisionType::class)]
class ProvisionTypeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'provision_database' => [
      static::cw(fn($test): string => $test->prompts[ProvisionType::id()] = ProvisionType::DATABASE),
    ];
    yield 'provision_database_lagoon' => [
      static::cw(function ($test): void {
          $test->prompts[ProvisionType::id()] = ProvisionType::DATABASE;
          $test->prompts[HostingProvider::id()] = HostingProvider::LAGOON;
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
    ];
    yield 'provision_profile' => [
      static::cw(function ($test): void {
          $test->prompts[ProvisionType::id()] = ProvisionType::PROFILE;
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
    ];
  }

}
