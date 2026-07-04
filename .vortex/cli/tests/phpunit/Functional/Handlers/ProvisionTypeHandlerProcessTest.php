<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class ProvisionTypeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'provision_database' => [
      self::cw(fn($test): string => $test->prompts['provision_type'] = 'database'),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('/VORTEX_PROVISION_SANITIZE_DB_/');
          $test->assertFileExists($test::$sut . '/scripts/sanitize.sql');
      }),
    ];
    yield 'provision_database_lagoon' => [
      self::cw(function ($test): void {
          $test->prompts['provision_type'] = 'database';
          $test->prompts['hosting_provider'] = 'lagoon';
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
    ];
    yield 'provision_profile' => [
      self::cw(function ($test): void {
          $test->prompts['provision_type'] = 'profile';
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains(['/VORTEX_FETCH_DB/', '/VORTEX_PROVISION_SANITIZE_DB_/', 'VORTEX_PROVISION_FALLBACK_TO_PROFILE']);
          $test->assertFileDoesNotExist($test::$sut . '/scripts/sanitize.sql');
      }),
    ];
    yield 'provision_profile_circleci' => [
      self::cw(function ($test): void {
          $test->prompts['provision_type'] = 'profile';
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutNotContains(['/VORTEX_FETCH_DB/', '/VORTEX_PROVISION_SANITIZE_DB_/', 'VORTEX_PROVISION_FALLBACK_TO_PROFILE', 'db_ssh_fingerprint']);
          $test->assertFileDoesNotExist($test::$sut . '/scripts/sanitize.sql');
      }),
    ];
  }

}
