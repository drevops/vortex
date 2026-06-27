<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationFetchSource;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Yaml;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(Migration::class)]
class MigrationHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'migration - prompt - disabled' => [
      [Migration::id() => Key::ENTER],
      [Migration::id() => FALSE] + $expected_defaults,
    ];
    yield 'migration - prompt - enabled' => [
      [
        Migration::id() => Key::LEFT . Key::ENTER,
        MigrationFetchSource::id() => Key::ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL] + $expected_defaults,
    ];
    yield 'migration - discovery - database2 exists' => [
      [
        MigrationFetchSource::id() => Key::ENTER,
      ],
      [Migration::id() => TRUE, MigrationFetchSource::id() => MigrationFetchSource::URL] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => ['database2' => [], 'antivirus' => [], 'cache' => [], 'search' => []]]));
      },
    ];
    yield 'migration - discovery - no database2 service' => [
      [],
      [Migration::id() => FALSE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => ['database' => [], 'antivirus' => [], 'cache' => [], 'search' => []]]));
      },
    ];
    yield 'migration - discovery - no docker-compose' => [
      [],
      $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
      },
    ];
    yield 'migration - discovery - non-Vortex' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        File::dump(static::$sut . '/docker-compose.yml', Yaml::dump(['services' => ['database2' => [], 'antivirus' => [], 'cache' => [], 'search' => []]]));
      },
    ];
  }

}
