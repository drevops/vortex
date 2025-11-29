<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\ProvisionType;
use DrevOps\VortexInstaller\Utils\Config;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProvisionType::class)]
class ProvisionTypeHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'provision type - prompt' => [
        [ProvisionType::id() => Key::ENTER],
        [ProvisionType::id() => ProvisionType::DATABASE] + $expected_defaults,
      ],

      'provision type - discovery - database' => [
        [],
        [ProvisionType::id() => ProvisionType::DATABASE] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::DATABASE);
        },
      ],

      'provision type - discovery - profile' => [
        [],
        [ProvisionType::id() => ProvisionType::PROFILE, DatabaseDownloadSource::id() => DatabaseDownloadSource::NONE] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::PROFILE);
        },
      ],

      'provision type - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          // No VORTEX_PROVISION_TYPE in .env - should fall back to default.
        },
      ],
    ];
  }

}
