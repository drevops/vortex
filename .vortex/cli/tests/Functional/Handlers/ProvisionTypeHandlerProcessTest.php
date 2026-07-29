<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexCli\Prompts\Handlers\CiProvider;
use DrevOps\VortexCli\Prompts\Handlers\HostingProvider;
use DrevOps\VortexCli\Prompts\Handlers\ProvisionType;
use DrevOps\VortexCli\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProvisionType::class)]
class ProvisionTypeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'provision_database' => [
      static::cw(fn($test): string => $test->prompts[ProvisionType::id()] = ProvisionType::DATABASE),
      static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('/VORTEX_PROVISION_SANITIZE_DB_/');
          $test->assertFileExists($test::$sut . '/scripts/sanitize.sql');
      }),
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
      static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains(['/VORTEX_FETCH_DB/', '/VORTEX_PROVISION_SANITIZE_DB_/', 'VORTEX_PROVISION_FALLBACK_TO_PROFILE']);
          $test->assertFileDoesNotExist($test::$sut . '/scripts/sanitize.sql');
      }),
    ];
    yield 'provision_profile_circleci' => [
      static::cw(function ($test): void {
          $test->prompts[ProvisionType::id()] = ProvisionType::PROFILE;
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutNotContains(['/VORTEX_FETCH_DB/', '/VORTEX_PROVISION_SANITIZE_DB_/', 'VORTEX_PROVISION_FALLBACK_TO_PROFILE', 'db_ssh_fingerprint']);
          $test->assertFileDoesNotExist($test::$sut . '/scripts/sanitize.sql');
      }),
    ];
  }

}
