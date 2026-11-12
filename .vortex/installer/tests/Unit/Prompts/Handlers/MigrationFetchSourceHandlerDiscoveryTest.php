<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationFetchSource;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MigrationFetchSource::class)]
class MigrationFetchSourceHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    yield 'migration download source - prompt' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL] + $expected_defaults,
    ];
    yield 'migration download source - prompt - ftp' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_DOWN . self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::FTP] + $expected_defaults,
    ];
    yield 'migration download source - discovery' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::FTP] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB2_SOURCE', MigrationFetchSource::FTP);
      },
    ];
    yield 'migration download source - discovery - s3' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::S3] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB2_SOURCE', MigrationFetchSource::S3);
      },
    ];
    yield 'migration download source - discovery - invalid' => [
      [
        Migration::id() => self::KEY_LEFT . self::KEY_ENTER,
        MigrationFetchSource::id() => self::KEY_ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB2_SOURCE', 'invalid_source');
      },
    ];
  }

}
