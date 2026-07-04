<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class DatabaseFetchSourceHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'db_fetch_source_url' => [
      self::cw(fn($test): string => $test->prompts['database_fetch_source'] = 'url'),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('VORTEX_FETCH_DB_URL');
          $test->assertSutNotContains(['/VORTEX_FETCH_DB_SSH_/', 'VORTEX_FETCH_DB_ENVIRONMENT', 'vortex-push-db-image', '/VORTEX_ACQUIA_/']);
      }),
    ];
    yield 'db_fetch_source_ftp' => [
      self::cw(fn($test): string => $test->prompts['database_fetch_source'] = 'ftp'),
    ];
    yield 'db_fetch_source_acquia' => [
      self::cw(fn($test): string => $test->prompts['database_fetch_source'] = 'acquia'),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains(['/VORTEX_ACQUIA_/', 'VORTEX_FETCH_DB_ENVIRONMENT']);
          $test->assertSutNotContains('/VORTEX_FETCH_DB_SSH_/');
      }),
    ];
    yield 'db_fetch_source_lagoon' => [
      self::cw(fn($test): string => $test->prompts['database_fetch_source'] = 'lagoon'),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains(['/VORTEX_FETCH_DB_SSH_/', 'VORTEX_FETCH_DB_ENVIRONMENT']);
          $test->assertSutNotContains('/VORTEX_ACQUIA_/');
      }),
    ];
    yield 'db_fetch_source_container_registry' => [
      self::cw(function ($test): void {
          $test->prompts['database_fetch_source'] = 'container_registry';
          $test->prompts['database_image'] = 'the_empire/star_wars:latest';
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('vortex-push-db-image');
          $test->assertSutNotContains(['/VORTEX_FETCH_DB_SSH_/', 'VORTEX_FETCH_DB_ENVIRONMENT']);
      }),
    ];
    yield 'db_fetch_source_s3' => [
      self::cw(fn($test): string => $test->prompts['database_fetch_source'] = 's3'),
    ];
  }

}
