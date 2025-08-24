<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AiCodeInstructions::class)]
class AiCodeInstructionsPromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'ai instructions - prompt' => [
        [AiCodeInstructions::id() => Key::ENTER],
        [AiCodeInstructions::id() => AiCodeInstructions::NONE] + $expected_defaults,
      ],

      'ai instructions - discovery' => [
        [],
        [AiCodeInstructions::id() => AiCodeInstructions::CLAUDE] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/CLAUDE.md');
        },
      ],

      'ai instructions - discovery - removed' => [
        [],
        [AiCodeInstructions::id() => AiCodeInstructions::NONE] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],

      'ai instructions - discovery - non-Vortex' => [
        [],
        [AiCodeInstructions::id() => AiCodeInstructions::NONE] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/CLAUDE.md');
        },
      ],

      'ai instructions - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No CLAUDE.md and not installed - should fall back to default.
        },
      ],
    ];
  }

}
