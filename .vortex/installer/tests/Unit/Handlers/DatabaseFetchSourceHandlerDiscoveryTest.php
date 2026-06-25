<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseFetchSource;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(DatabaseFetchSource::class)]
class DatabaseFetchSourceHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    yield 'database fetch source - prompt' => [
      [DatabaseFetchSource::id() => Key::ENTER],
      [DatabaseFetchSource::id() => DatabaseFetchSource::URL] + $expected_defaults,
    ];
    yield 'database fetch source - discovery' => [
      [],
      [DatabaseFetchSource::id() => DatabaseFetchSource::FTP] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB_SOURCE', DatabaseFetchSource::FTP);
      },
    ];
    yield 'database fetch source - discovery - s3' => [
      [],
      [DatabaseFetchSource::id() => DatabaseFetchSource::S3] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB_SOURCE', DatabaseFetchSource::S3);
      },
    ];
    yield 'database fetch source - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        $test->stubDotenvValue('VORTEX_FETCH_DB_SOURCE', 'invalid_source');
      },
    ];
  }

}
