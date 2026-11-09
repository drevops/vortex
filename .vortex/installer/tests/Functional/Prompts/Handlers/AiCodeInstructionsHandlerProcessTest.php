<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AiCodeInstructions::class)]
class AiCodeInstructionsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'ai_instructions_enabled' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): true => $test->prompts[AiCodeInstructions::id()] = TRUE),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileExists(static::$sut . '/AGENTS.md');
          $test->assertFileExists(static::$sut . '/CLAUDE.md');
          $test->assertFileExists(static::$sut . '/.claude/settings.json');
          $test->assertFileNotContainsString(static::$sut . '/.claude/settings.json', 'extraKnownMarketplaces');
          $test->assertFileNotContainsString(static::$sut . '/.claude/settings.json', 'enabledPlugins');
          $test->assertFileExists(static::$sut . '/.claude/skills/review-code/SKILL.md');
          $test->assertFileExists(static::$sut . '/.claude/skills/review-code/checklist.md');
          // Snapshots skip broken links, and this link resolves only after
          // 'composer install'.
          $test->assertTrue(is_link(static::$sut . '/.claude/skills/create-behat-step'));
          $test->assertSame('../../vendor/drevops/behat-steps/skills/create-behat-step', readlink(static::$sut . '/.claude/skills/create-behat-step'));
      }),
    ];
    yield 'ai_instructions_disabled' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): false => $test->prompts[AiCodeInstructions::id()] = FALSE),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/AGENTS.md');
          $test->assertFileDoesNotExist(static::$sut . '/CLAUDE.md');
          $test->assertDirectoryDoesNotExist(static::$sut . '/.claude');
      }),
    ];
  }

}
