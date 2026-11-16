<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationFetchSource;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationFetchStore;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationImage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MigrationFetchStore::class)]
class MigrationFetchStoreHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    yield 'migration fetch store - prompt' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_ENTER,
        MigrationFetchStore::id() => self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL, MigrationFetchStore::id() => MigrationFetchStore::NONE] + $expected_defaults,
    ];
    yield 'migration fetch store - prompt - ftp' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_ENTER,
        MigrationFetchStore::id() => self::KEY_DOWN . self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL, MigrationFetchStore::id() => MigrationFetchStore::FTP] + $expected_defaults,
    ];
    yield 'migration fetch store - prompt - source excluded' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_DOWN . self::KEY_ENTER,
        MigrationFetchStore::id() => self::KEY_DOWN . self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::FTP, MigrationFetchStore::id() => MigrationFetchStore::S3] + $expected_defaults,
    ];
    yield 'migration fetch store - skipped - container registry' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => str_repeat(self::KEY_DOWN, 4) . self::KEY_ENTER,
        MigrationImage::id() => static::TUI_DEFAULT,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::CONTAINER_REGISTRY, MigrationImage::id() => 'myprojectorg/myproject-data-migration:latest'] + $expected_defaults,
    ];
    yield 'migration fetch store - skipped - migration disabled' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB2_STORE', MigrationFetchStore::FTP);
      },
    ];
    yield 'migration fetch store - discovery - ftp' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_ENTER,
        MigrationFetchStore::id() => self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL, MigrationFetchStore::id() => MigrationFetchStore::FTP] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB2_STORE', MigrationFetchStore::FTP);
      },
    ];
    yield 'migration fetch store - discovery - s3' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_ENTER,
        MigrationFetchStore::id() => self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL, MigrationFetchStore::id() => MigrationFetchStore::S3] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB2_STORE', MigrationFetchStore::S3);
      },
    ];
    yield 'migration fetch store - discovery - empty' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_ENTER,
        MigrationFetchStore::id() => self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL, MigrationFetchStore::id() => MigrationFetchStore::NONE] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB2_STORE', '');
      },
    ];
    yield 'migration fetch store - discovery - invalid' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_ENTER,
        MigrationFetchStore::id() => self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL, MigrationFetchStore::id() => MigrationFetchStore::NONE] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB2_STORE', 'invalid_store');
      },
    ];
  }

}
