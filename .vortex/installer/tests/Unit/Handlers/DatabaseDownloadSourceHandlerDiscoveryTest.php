<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(DatabaseDownloadSource::class)]
class DatabaseDownloadSourceHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'database download source - prompt' => [
        [DatabaseDownloadSource::id() => Key::ENTER],
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::URL] + $expected_defaults,
      ],

      'database download source - discovery' => [
        [],
        [DatabaseDownloadSource::id() => DatabaseDownloadSource::FTP] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubDotenvValue('VORTEX_DB_DOWNLOAD_SOURCE', DatabaseDownloadSource::FTP);
        },
      ],

      'database download source - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          $test->stubDotenvValue('VORTEX_DB_DOWNLOAD_SOURCE', 'invalid_source');
        },
      ],

    ];
  }

}
