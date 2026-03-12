<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AiCodeInstructions::class)]
class AiCodeInstructionsHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'ai instructions - prompt' => [
      [AiCodeInstructions::id() => Key::ENTER],
      [AiCodeInstructions::id() => TRUE] + $expected_defaults,
    ];
    yield 'ai instructions - discovery' => [
      [],
      [AiCodeInstructions::id() => TRUE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/AGENTS.md');
      },
    ];
    yield 'ai instructions - discovery - claude only' => [
      [],
      [AiCodeInstructions::id() => TRUE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/CLAUDE.md');
      },
    ];
    yield 'ai instructions - discovery - removed' => [
      [],
      [AiCodeInstructions::id() => FALSE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
      },
    ];
    yield 'ai instructions - discovery - non-Vortex' => [
      [],
      [AiCodeInstructions::id() => TRUE] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        File::dump(static::$sut . '/AGENTS.md');
      },
    ];
    yield 'ai instructions - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        // No AGENTS.md and not installed - should fall back to default.
      },
    ];
  }

}
