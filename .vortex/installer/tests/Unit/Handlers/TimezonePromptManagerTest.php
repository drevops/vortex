<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Timezone;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Timezone::class)]
class TimezonePromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'timezone - prompt' => [
        [Timezone::id() => 'America/New_York'],
        [Timezone::id() => 'America/New_York'] + $expected_defaults,
      ],

      'timezone - discovery' => [
        [],
        [Timezone::id() => 'Europe/London'] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('TZ', 'Europe/London');
        },
      ],

      'timezone - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No TZ in .env - should fall back to default.
        },
      ],
    ];
  }

}
