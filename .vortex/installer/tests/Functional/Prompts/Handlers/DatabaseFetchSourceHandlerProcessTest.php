<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseFetchStore;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DatabaseFetchSource::class)]
#[CoversClass(DatabaseFetchStore::class)]
#[CoversClass(DatabaseImage::class)]
class DatabaseFetchSourceHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'db_fetch_source_url' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): string => $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::URL),
    ];
    yield 'db_fetch_source_ftp' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): string => $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::FTP),
    ];
    yield 'db_fetch_source_acquia' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): string => $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::ACQUIA),
    ];
    yield 'db_fetch_source_lagoon' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): string => $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::LAGOON),
    ];
    yield 'db_fetch_source_container_registry' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::CONTAINER_REGISTRY;
          $test->prompts[DatabaseImage::id()] = 'the_empire/star_wars:latest';
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
    ];
    yield 'db_fetch_source_s3' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): string => $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::S3),
    ];
    yield 'db_fetch_store_ftp' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::LAGOON;
          $test->prompts[DatabaseFetchStore::id()] = DatabaseFetchStore::FTP;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB_SOURCE=lagoon');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB_STORE=ftp');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB_FTP_HOST=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB_S3_BUCKET');
      }),
    ];
    yield 'db_fetch_store_s3' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[DatabaseFetchSource::id()] = DatabaseFetchSource::URL;
          $test->prompts[DatabaseFetchStore::id()] = DatabaseFetchStore::S3;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB_SOURCE=url');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB_STORE=s3');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB_S3_BUCKET=');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_FETCH_DB_FTP_HOST');
      }),
    ];
  }

}
