<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ModulePrefix::class)]
class ModulePrefixPromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'module prefix - prompt' => [
        [ModulePrefix::id() => 'myprefix'],
        [ModulePrefix::id() => 'myprefix'] + $expected_defaults,
      ],

      'module prefix - prompt - override' => [
        [ModulePrefix::id() => 'myprefix'],
        [ModulePrefix::id() => 'myprefix'] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/custom/discovered_profile/modules/custom/dp_base/dp_base.info');
        },
      ],

      'module prefix - prompt - invalid' => [
        [ModulePrefix::id() => 'my prefix'],
        'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'module prefix - prompt - invalid - capitalization' => [
        [ModulePrefix::id() => 'MyPrefix'],
        'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'module prefix - discovery' => [
        [],
        [ModulePrefix::id() => 'dp'] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/web/modules/custom/dp_base/dp_base.info');
        },
      ],

      'module prefix - discovery - core' => [
        [],
        [ModulePrefix::id() => 'dp'] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/web/modules/custom/dp_core/dp_core.info');
        },
      ],

      'module prefix - discovery - within profile' => [
        [],
        [ModulePrefix::id() => 'dp'] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/custom/discovered_profile/modules/custom/dp_base/dp_base.info');
        },
      ],

      'module prefix - discovery - within profile - core' => [
        [],
        [ModulePrefix::id() => 'dp'] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/custom/discovered_profile/modules/custom/dp_core/dp_core.info');
        },
      ],

      'module prefix - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No *_base or *_core modules exist - should fall back to default.
        },
      ],
    ];
  }

}
