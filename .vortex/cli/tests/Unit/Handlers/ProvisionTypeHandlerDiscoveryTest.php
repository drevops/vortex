<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\DatabaseFetchSource;
use DrevOps\VortexCli\Prompts\Handlers\ProvisionType;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Tests\Support\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProvisionType::class)]
class ProvisionTypeHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    yield 'provision type - prompt' => [
      [ProvisionType::id() => Key::ENTER],
      [ProvisionType::id() => ProvisionType::DATABASE] + $expected_defaults,
    ];
    yield 'provision type - discovery - database' => [
      [],
      [ProvisionType::id() => ProvisionType::DATABASE] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::DATABASE);
      },
    ];
    yield 'provision type - discovery - profile' => [
      [],
      // Provisioning from a profile never asks where to fetch a database, so
      // the question is left unanswered rather than answered "none".
      [ProvisionType::id() => ProvisionType::PROFILE, DatabaseFetchSource::id() => NULL] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubDotenvValue('VORTEX_PROVISION_TYPE', ProvisionType::PROFILE);
      },
    ];
    yield 'provision type - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        // No VORTEX_PROVISION_TYPE in .env - should fall back to default.
      },
    ];
  }

}
