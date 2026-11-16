<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseFetchStore;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DatabaseFetchStore::class)]
class DatabaseFetchStoreHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    yield 'database fetch store - prompt' => [
      [DatabaseFetchStore::id() => self::KEY_ENTER],
      [DatabaseFetchStore::id() => DatabaseFetchStore::NONE] + $expected_defaults,
    ];
    yield 'database fetch store - prompt - ftp' => [
      [DatabaseFetchStore::id() => self::KEY_DOWN . self::KEY_ENTER],
      [DatabaseFetchStore::id() => DatabaseFetchStore::FTP] + $expected_defaults,
    ];
    yield 'database fetch store - prompt - s3' => [
      [DatabaseFetchStore::id() => self::KEY_DOWN . self::KEY_DOWN . self::KEY_ENTER],
      [DatabaseFetchStore::id() => DatabaseFetchStore::S3] + $expected_defaults,
    ];
    yield 'database fetch store - prompt - source excluded' => [
      [
        DatabaseFetchSource::id() => self::KEY_DOWN . self::KEY_ENTER,
        DatabaseFetchStore::id() => self::KEY_DOWN . self::KEY_ENTER,
      ],
      [DatabaseFetchSource::id() => DatabaseFetchSource::FTP, DatabaseFetchStore::id() => DatabaseFetchStore::S3] + $expected_defaults,
    ];
    yield 'database fetch store - skipped - source none' => [
      [
        DatabaseFetchSource::id() => str_repeat(self::KEY_DOWN, 6) . self::KEY_ENTER,
        DatabaseFetchStore::id() => static::TUI_SKIP,
      ],
      [DatabaseFetchSource::id() => DatabaseFetchSource::NONE, DatabaseFetchStore::id() => NULL] + $expected_defaults,
    ];
    yield 'database fetch store - discovery - ftp' => [
      [],
      [DatabaseFetchStore::id() => DatabaseFetchStore::FTP] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB_STORE', DatabaseFetchStore::FTP);
      },
    ];
    yield 'database fetch store - discovery - s3' => [
      [],
      [DatabaseFetchStore::id() => DatabaseFetchStore::S3] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB_STORE', DatabaseFetchStore::S3);
      },
    ];
    yield 'database fetch store - discovery - empty' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB_STORE', '');
      },
    ];
    yield 'database fetch store - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB_STORE', 'invalid_store');
      },
    ];
  }

}
