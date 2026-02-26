<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationDownloadSource;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MigrationDownloadSource::class)]
class MigrationDownloadSourceHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'migration_download_source_url' => [
        static::cw(function (): void {
          Env::put(Migration::envName(), Env::TRUE);
          Env::put(MigrationDownloadSource::envName(), MigrationDownloadSource::URL);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=url');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_S3_BUCKET');
        }),
      ],

      'migration_download_source_ftp' => [
        static::cw(function (): void {
          Env::put(Migration::envName(), Env::TRUE);
          Env::put(MigrationDownloadSource::envName(), MigrationDownloadSource::FTP);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=ftp');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_S3_BUCKET');
        }),
      ],

      'migration_download_source_acquia' => [
        static::cw(function (): void {
          Env::put(Migration::envName(), Env::TRUE);
          Env::put(MigrationDownloadSource::envName(), MigrationDownloadSource::ACQUIA);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=acquia');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_S3_BUCKET');
        }),
      ],

      'migration_download_source_lagoon' => [
        static::cw(function (): void {
          Env::put(Migration::envName(), Env::TRUE);
          Env::put(MigrationDownloadSource::envName(), MigrationDownloadSource::LAGOON);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=lagoon');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_FTP_HOST');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_S3_BUCKET');
        }),
      ],

      'migration_download_source_s3' => [
        static::cw(function (): void {
          Env::put(Migration::envName(), Env::TRUE);
          Env::put(MigrationDownloadSource::envName(), MigrationDownloadSource::S3);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=s3');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_S3_BUCKET');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_URL=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_FTP_HOST');
        }),
      ],
    ];
  }

}
