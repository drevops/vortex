<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\Services;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Yaml;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(Migration::class)]
class MigrationHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'migration - prompt - disabled' => [
        [Migration::id() => Key::ENTER],
        [Migration::id() => FALSE] + $expected_defaults,
      ],

      'migration - prompt - enabled' => [
        [
          Migration::id() => Key::LEFT . Key::ENTER,
          MigrationDownloadSource::id() => Key::ENTER,
        ],
        [Migration::id() => TRUE, MigrationDownloadSource::id() => MigrationDownloadSource::URL] + $expected_defaults,
      ],

      'migration - discovery - database2 exists' => [
        [
          MigrationDownloadSource::id() => Key::ENTER,
        ],
        [Migration::id() => TRUE, MigrationDownloadSource::id() => MigrationDownloadSource::URL] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => ['database2' => [], Services::CLAMAV => [], Services::REDIS => [], Services::SOLR => []]]));
        },
      ],

      'migration - discovery - no database2 service' => [
        [],
        [Migration::id() => FALSE] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => ['database' => [], Services::CLAMAV => [], Services::REDIS => [], Services::SOLR => []]]));
        },
      ],

      'migration - discovery - no docker-compose' => [
        [],
        $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],

      'migration - discovery - non-Vortex' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => ['database2' => [], Services::CLAMAV => [], Services::REDIS => [], Services::SOLR => []]]));
        },
      ],
    ];
  }

}
