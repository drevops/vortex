<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationImage;
use DrevOps\VortexInstaller\Utils\Config;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MigrationImage::class)]
class MigrationImageHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    yield 'migration image - default' => [
      [
        Migration::id() => Key::LEFT . Key::ENTER,
        MigrationDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
        MigrationImage::id() => static::TUI_DEFAULT,
      ],
      [Migration::id() => TRUE, MigrationDownloadSource::id() => MigrationDownloadSource::CONTAINER_REGISTRY, MigrationImage::id() => 'myprojectorg/myproject-data-migration:latest'] + $expected_defaults,
    ];
    yield 'migration image - invalid' => [
      [
        Migration::id() => Key::LEFT . Key::ENTER,
        MigrationDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
        MigrationImage::id() => 'myregistry:myimage:mytag',
      ],
      'Please enter a valid container image name with an optional tag.',
    ];
    yield 'migration image - discovery' => [
      [
        Migration::id() => Key::LEFT . Key::ENTER,
        MigrationDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
      ],
      [
        Migration::id() => TRUE,
        MigrationDownloadSource::id() => MigrationDownloadSource::CONTAINER_REGISTRY,
        MigrationImage::id() => 'discovered_owner/discovered_migration:tag',
      ] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubDotenvValue('VORTEX_DB2_IMAGE', 'discovered_owner/discovered_migration:tag');
      },
    ];
  }

}
