<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Lighthouse;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Lighthouse::class)]
class LighthouseHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    yield 'lighthouse - prompt - disabled' => [
      [Lighthouse::id() => self::KEY_ENTER],
      [Lighthouse::id() => FALSE] + $expected_defaults,
    ];
    yield 'lighthouse - prompt - enabled' => [
      [Lighthouse::id() => self::KEY_LEFT . self::KEY_ENTER],
      [Lighthouse::id() => TRUE] + $expected_defaults,
    ];
    yield 'lighthouse - discovery - command present' => [
      [],
      [Lighthouse::id() => TRUE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/.ahoy.yml', "commands:\n  test-lighthouse:\n    cmd: docker compose run --rm audit ./vendor/bin/vortex-lighthouse\n");
      },
    ];
    yield 'lighthouse - discovery - command absent' => [
      [],
      [Lighthouse::id() => FALSE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/.ahoy.yml', "commands:\n  test-bdd:\n    cmd: ahoy cli vendor/bin/behat\n");
      },
    ];
  }

}
