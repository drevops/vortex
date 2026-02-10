<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationDownloadSource;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(MigrationDownloadSource::class)]
class MigrationDownloadSourceHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'migration download source - prompt' => [
        [
          Migration::id() => Key::LEFT . Key::ENTER,
          MigrationDownloadSource::id() => Key::ENTER,
        ],
        [Migration::id() => TRUE, MigrationDownloadSource::id() => MigrationDownloadSource::URL] + $expected_defaults,
      ],

      'migration download source - prompt - ftp' => [
        [
          Migration::id() => Key::LEFT . Key::ENTER,
          MigrationDownloadSource::id() => Key::DOWN . Key::ENTER,
        ],
        [Migration::id() => TRUE, MigrationDownloadSource::id() => MigrationDownloadSource::FTP] + $expected_defaults,
      ],

      'migration download source - discovery' => [
        [
          Migration::id() => Key::LEFT . Key::ENTER,
        ],
        [Migration::id() => TRUE, MigrationDownloadSource::id() => MigrationDownloadSource::FTP] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubDotenvValue('VORTEX_DOWNLOAD_DB2_SOURCE', MigrationDownloadSource::FTP);
        },
      ],

      'migration download source - discovery - s3' => [
        [
          Migration::id() => Key::LEFT . Key::ENTER,
        ],
        [Migration::id() => TRUE, MigrationDownloadSource::id() => MigrationDownloadSource::S3] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubDotenvValue('VORTEX_DOWNLOAD_DB2_SOURCE', MigrationDownloadSource::S3);
        },
      ],

      'migration download source - discovery - invalid' => [
        [
          Migration::id() => Key::LEFT . Key::ENTER,
          MigrationDownloadSource::id() => Key::ENTER,
        ],
        [Migration::id() => TRUE, MigrationDownloadSource::id() => MigrationDownloadSource::URL] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubDotenvValue('VORTEX_DOWNLOAD_DB2_SOURCE', 'invalid_source');
        },
      ],
    ];
  }

}
