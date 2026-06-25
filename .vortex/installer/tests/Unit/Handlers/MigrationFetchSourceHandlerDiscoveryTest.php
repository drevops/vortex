<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationFetchSource;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(MigrationFetchSource::class)]
class MigrationFetchSourceHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    yield 'migration download source - prompt' => [
      [
        Migration::id() => Key::LEFT . Key::ENTER,
        MigrationFetchSource::id() => Key::ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL] + $expected_defaults,
    ];
    yield 'migration download source - prompt - ftp' => [
      [
        Migration::id() => Key::LEFT . Key::ENTER,
        MigrationFetchSource::id() => Key::DOWN . Key::ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::FTP] + $expected_defaults,
    ];
    yield 'migration download source - discovery' => [
      [
        Migration::id() => Key::LEFT . Key::ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::FTP] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB2_SOURCE', MigrationFetchSource::FTP);
      },
    ];
    yield 'migration download source - discovery - s3' => [
      [
        Migration::id() => Key::LEFT . Key::ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::S3] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB2_SOURCE', MigrationFetchSource::S3);
      },
    ];
    yield 'migration download source - discovery - invalid' => [
      [
        Migration::id() => Key::LEFT . Key::ENTER,
        MigrationFetchSource::id() => Key::ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB2_SOURCE', 'invalid_source');
      },
    ];
  }

}
