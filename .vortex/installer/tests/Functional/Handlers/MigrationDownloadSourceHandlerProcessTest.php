<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationImage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MigrationDownloadSource::class)]
class MigrationDownloadSourceHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'migration_download_source_url' => [
      static::cw(function ($test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationDownloadSource::id()] = MigrationDownloadSource::URL;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=url');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_S3_BUCKET');
      }),
    ];
    yield 'migration_download_source_ftp' => [
      static::cw(function ($test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationDownloadSource::id()] = MigrationDownloadSource::FTP;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=ftp');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_S3_BUCKET');
      }),
    ];
    yield 'migration_download_source_acquia' => [
      static::cw(function ($test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationDownloadSource::id()] = MigrationDownloadSource::ACQUIA;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=acquia');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_S3_BUCKET');
      }),
    ];
    yield 'migration_download_source_lagoon' => [
      static::cw(function ($test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationDownloadSource::id()] = MigrationDownloadSource::LAGOON;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=lagoon');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_S3_BUCKET');
      }),
    ];
    yield 'migration_download_source_s3' => [
      static::cw(function ($test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationDownloadSource::id()] = MigrationDownloadSource::S3;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=s3');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_S3_BUCKET');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_FTP_HOST');
      }),
    ];
    yield 'migration_download_source_container_registry' => [
      static::cw(function ($test): void {
          $test->prompts[Migration::id()] = TRUE;
          $test->prompts[MigrationDownloadSource::id()] = MigrationDownloadSource::CONTAINER_REGISTRY;
          $test->prompts[MigrationImage::id()] = 'the_empire/star_wars-migration:latest';
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=container_registry');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DB2_IMAGE=the_empire/star_wars-migration:latest');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_S3_BUCKET');
      }),
    ];
  }

}
