<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(DatabaseImage::class)]
class DatabaseImageHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    yield 'database image - prompt' => [
      [
        DatabaseFetchSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
        DatabaseImage::id() => 'myregistry/myimage:mytag',
      ],
      [DatabaseFetchSource::id() => DatabaseFetchSource::CONTAINER_REGISTRY, DatabaseImage::id() => 'myregistry/myimage:mytag'] + $expected_defaults,
    ];
    yield 'database image - invalid' => [
      [
        DatabaseFetchSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
        DatabaseImage::id() => 'myregistry:myimage:mytag',
      ],
      'Please enter a valid container image name with an optional tag.',
    ];
    yield 'database image - invalid - capitalization' => [
      [
        DatabaseFetchSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
        DatabaseImage::id() => 'MyRegistry/MyImage:mytag',
      ],
      'Please enter a valid container image name with an optional tag.',
    ];
    yield 'database image - discovery' => [
      [
        DatabaseFetchSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
      ],
      [
        DatabaseFetchSource::id() => DatabaseFetchSource::CONTAINER_REGISTRY,
        DatabaseImage::id() => 'discovered_owner/discovered_image:tag',
      ] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubDotenvValue('VORTEX_DB_IMAGE', 'discovered_owner/discovered_image:tag');
      },
    ];
  }

}
