<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class AiCodeInstructionsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'ai_instructions_enabled' => [
      self::cw(fn($test): true => $test->prompts['ai_code_instructions'] = TRUE),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileExists(self::$sut . '/AGENTS.md');
          $test->assertFileExists(self::$sut . '/CLAUDE.md');
          $test->assertFileExists(self::$sut . '/.claude/settings.json');
      }),
    ];
    yield 'ai_instructions_disabled' => [
      self::cw(fn($test): false => $test->prompts['ai_code_instructions'] = FALSE),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(self::$sut . '/AGENTS.md');
          $test->assertFileDoesNotExist(self::$sut . '/CLAUDE.md');
          $test->assertDirectoryDoesNotExist(self::$sut . '/.claude');
      }),
    ];
  }

}
