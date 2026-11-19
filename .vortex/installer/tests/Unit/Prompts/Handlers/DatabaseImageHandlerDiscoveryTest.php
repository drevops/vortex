<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseFetchStore;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DatabaseImage::class)]
class DatabaseImageHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    yield 'database image - prompt' => [
      [
        DatabaseFetchSource::id() => self::KEY_DOWN . self::KEY_DOWN . self::KEY_DOWN . self::KEY_DOWN . self::KEY_ENTER,
        DatabaseImage::id() => 'myregistry/myimage:mytag',
        DatabaseFetchStore::id() => static::TUI_SKIP,
      ],
      [DatabaseFetchSource::id() => DatabaseFetchSource::CONTAINER_REGISTRY, DatabaseImage::id() => 'myregistry/myimage:mytag', DatabaseFetchStore::id() => NULL] + $expected_defaults,
    ];
    yield 'database image - invalid' => [
      [
        DatabaseFetchSource::id() => self::KEY_DOWN . self::KEY_DOWN . self::KEY_DOWN . self::KEY_DOWN . self::KEY_ENTER,
        DatabaseImage::id() => 'myregistry:myimage:mytag',
        DatabaseFetchStore::id() => static::TUI_SKIP,
      ],
      'Please enter a valid container image name with an optional tag.',
    ];
    yield 'database image - invalid - capitalization' => [
      [
        DatabaseFetchSource::id() => self::KEY_DOWN . self::KEY_DOWN . self::KEY_DOWN . self::KEY_DOWN . self::KEY_ENTER,
        DatabaseImage::id() => 'MyRegistry/MyImage:mytag',
        DatabaseFetchStore::id() => static::TUI_SKIP,
      ],
      'Please enter a valid container image name with an optional tag.',
    ];
    yield 'database image - discovery' => [
      [
        DatabaseFetchSource::id() => self::KEY_DOWN . self::KEY_DOWN . self::KEY_DOWN . self::KEY_DOWN . self::KEY_ENTER,
        DatabaseImage::id() => static::TUI_DEFAULT,
        DatabaseFetchStore::id() => static::TUI_SKIP,
      ],
      [
        DatabaseFetchSource::id() => DatabaseFetchSource::CONTAINER_REGISTRY,
        DatabaseImage::id() => 'discovered_owner/discovered_image:tag',
        DatabaseFetchStore::id() => NULL,
      ] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubDotenvValue('VORTEX_DB_IMAGE', 'discovered_owner/discovered_image:tag');
      },
    ];
  }

}
