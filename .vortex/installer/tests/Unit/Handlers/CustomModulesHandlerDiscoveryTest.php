<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CustomModules;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CustomModules::class)]
class CustomModulesHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'custom_modules - prompt' => [
        [CustomModules::id() => Key::ENTER],
        [CustomModules::id() => [CustomModules::BASE, CustomModules::SEARCH, CustomModules::DEMO]] + $expected_defaults,
      ],

      'custom_modules - discovery - all' => [
        [],
        [CustomModules::id() => [CustomModules::BASE, CustomModules::DEMO, CustomModules::SEARCH]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::mkdir(static::$sut . '/web/modules/custom/mypr_base');
          File::mkdir(static::$sut . '/web/modules/custom/mypr_demo');
          File::mkdir(static::$sut . '/web/modules/custom/mypr_search');
        },
      ],

      'custom_modules - discovery - base only' => [
        [],
        [CustomModules::id() => [CustomModules::BASE]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::mkdir(static::$sut . '/web/modules/custom/mypr_base');
        },
      ],

      'custom_modules - discovery - base and demo' => [
        [],
        [CustomModules::id() => [CustomModules::BASE, CustomModules::DEMO]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::mkdir(static::$sut . '/web/modules/custom/mypr_base');
          File::mkdir(static::$sut . '/web/modules/custom/mypr_demo');
        },
      ],

      'custom_modules - discovery - base and search' => [
        [],
        [CustomModules::id() => [CustomModules::BASE, CustomModules::SEARCH]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::mkdir(static::$sut . '/web/modules/custom/mypr_base');
          File::mkdir(static::$sut . '/web/modules/custom/mypr_search');
        },
      ],

      'custom_modules - discovery - no prefix found' => [
        [],
        [CustomModules::id() => [CustomModules::BASE, CustomModules::SEARCH, CustomModules::DEMO]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          // No *_base or *_core directory exists, so prefix cannot be
          // discovered and discover() returns NULL, falling back to defaults.
        },
      ],

      'custom_modules - discovery - non-Vortex project' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          // Not a Vortex project - discovery should not run, defaults apply.
          File::mkdir(static::$sut . '/web/modules/custom/mypr_base');
          File::mkdir(static::$sut . '/web/modules/custom/mypr_demo');
        },
      ],
    ];
  }

}
