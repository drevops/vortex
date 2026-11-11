<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationFetchSource;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationImage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MigrationFetchSource::class)]
class MigrationFetchSourceHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'migration_fetch_source_url' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationFetchSource::id()] = MigrationFetchSource::URL;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=url');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_REFRESH_DB2_PROCEED');
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'VORTEX_DB_INDEX=2 ./vendor/bin/vortex-refresh-db');
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'VORTEX_DB_IMAGE_BASE');
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'Deploy DB image');
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'VORTEX_DB_INDEX=2 ./vendor/bin/vortex-push-db');
      }),
    ];
    yield 'migration_fetch_source_ftp' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationFetchSource::id()] = MigrationFetchSource::FTP;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=ftp');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
          $test->assertFileContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'VORTEX_DB_INDEX=2 ./vendor/bin/vortex-push-db');
      }),
    ];
    yield 'migration_fetch_source_acquia' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationFetchSource::id()] = MigrationFetchSource::ACQUIA;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=acquia');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
      }),
    ];
    yield 'migration_fetch_source_lagoon' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationFetchSource::id()] = MigrationFetchSource::LAGOON;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=lagoon');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
      }),
    ];
    yield 'migration_fetch_source_s3' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationFetchSource::id()] = MigrationFetchSource::S3;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=s3');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
          $test->assertFileContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'VORTEX_DB_INDEX=2 ./vendor/bin/vortex-push-db');
      }),
    ];
    yield 'migration_fetch_source_container_registry' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationFetchSource::id()] = MigrationFetchSource::CONTAINER_REGISTRY;
          $test->prompts[MigrationImage::id()] = 'the_empire/star_wars-migration:latest';
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE=container_registry');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DB2_IMAGE=the_empire/star_wars-migration:latest');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB2_S3_BUCKET');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_REFRESH_DB2_PROCEED');
          $test->assertFileContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'VORTEX_DB_INDEX=2 ./vendor/bin/vortex-refresh-db');
          // The main database stays on a dump file, so only the shared nightly
          // variables ship.
          $test->assertFileContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'VORTEX_DB_IMAGE_BASE');
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'Deploy DB image');
      }),
    ];
  }

}
