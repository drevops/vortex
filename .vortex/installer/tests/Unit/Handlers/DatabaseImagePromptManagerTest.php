<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(DatabaseImage::class)]
class DatabaseImagePromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'database image - prompt' => [
        [
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          DatabaseImage::id() => 'myregistry/myimage:mytag',
        ],
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::CONTAINER_REGISTRY, DatabaseImage::id() => 'myregistry/myimage:mytag'] + $expected_defaults,
      ],

      'database image - invalid' => [
        [
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          DatabaseImage::id() => 'myregistry:myimage:mytag',
        ],
        'Please enter a valid container image name with an optional tag.',
      ],

      'database image - invalid - capitalization' => [
        [
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
          DatabaseImage::id() => 'MyRegistry/MyImage:mytag',
        ],
        'Please enter a valid container image name with an optional tag.',
      ],

      'database image - discovery' => [
        [
          DatabaseDownloadSource::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER,
        ],
        [
          DatabaseDownloadSource::id() => DatabaseDownloadSource::CONTAINER_REGISTRY,
          DatabaseImage::id() => 'discovered_owner/discovered_image:tag',
        ] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_DB_IMAGE', 'discovered_owner/discovered_image:tag');
        },
      ],
    ];
  }

}
