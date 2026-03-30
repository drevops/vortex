<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AiCodeInstructions::class)]
class AiCodeInstructionsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'ai_instructions_enabled' => [
      static::cw(fn($test): true => $test->prompts[AiCodeInstructions::id()] = TRUE),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileExists(static::$sut . '/AGENTS.md');
          $test->assertFileExists(static::$sut . '/CLAUDE.md');
      }),
    ];
    yield 'ai_instructions_disabled' => [
      static::cw(fn($test): false => $test->prompts[AiCodeInstructions::id()] = FALSE),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/AGENTS.md');
          $test->assertFileDoesNotExist(static::$sut . '/CLAUDE.md');
      }),
    ];
  }

}
