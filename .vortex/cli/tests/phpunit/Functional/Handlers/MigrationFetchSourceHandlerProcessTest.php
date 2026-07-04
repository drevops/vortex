<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class MigrationFetchSourceHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'migration_fetch_source_url' => [
      self::cw(function ($test): void {
          $test->prompts['migration'] = TRUE;
          $test->prompts['migration_fetch_source'] = 'url';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=url');
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
          $test->assertSutNotContains(['/VORTEX_FETCH_DB_SSH_/', '/VORTEX_ACQUIA_/']);
      }),
    ];
    yield 'migration_fetch_source_ftp' => [
      self::cw(function ($test): void {
          $test->prompts['migration'] = TRUE;
          $test->prompts['migration_fetch_source'] = 'ftp';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=ftp');
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
      }),
    ];
    yield 'migration_fetch_source_acquia' => [
      self::cw(function ($test): void {
          $test->prompts['migration'] = TRUE;
          $test->prompts['migration_fetch_source'] = 'acquia';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=acquia');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
          $test->assertSutContains('/VORTEX_ACQUIA_/');
          $test->assertSutNotContains('/VORTEX_FETCH_DB_SSH_/');
      }),
    ];
    yield 'migration_fetch_source_lagoon' => [
      self::cw(function ($test): void {
          $test->prompts['migration'] = TRUE;
          $test->prompts['migration_fetch_source'] = 'lagoon';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=lagoon');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
          $test->assertSutContains(['/VORTEX_FETCH_DB_SSH_/', 'VORTEX_FETCH_DB2_ENVIRONMENT']);
          $test->assertSutNotContains(['/VORTEX_ACQUIA_/', 'VORTEX_FETCH_DB_ENVIRONMENT']);
      }),
    ];
    yield 'migration_fetch_source_s3' => [
      self::cw(function ($test): void {
          $test->prompts['migration'] = TRUE;
          $test->prompts['migration_fetch_source'] = 's3';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=s3');
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
      }),
    ];
    yield 'migration_fetch_source_container_registry' => [
      self::cw(function ($test): void {
          $test->prompts['migration'] = TRUE;
          $test->prompts['migration_fetch_source'] = 'container_registry';
          $test->prompts['migration_image'] = 'the_empire/star_wars-migration:latest';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=container_registry');
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_DB2_IMAGE=the_empire/star_wars-migration:latest');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
      }),
    ];
  }

}
