<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Timezone;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Timezone::class)]
class TimezoneHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'timezone - prompt' => [
      [Timezone::id() => 'America/New_York'],
      [Timezone::id() => 'America/New_York'] + $expected_defaults,
    ];
    yield 'timezone - discovery' => [
      [],
      [Timezone::id() => 'Europe/London'] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('TZ', 'Europe/London');
      },
    ];
    yield 'timezone - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        // No TZ in .env - should fall back to default.
      },
    ];
  }

}
