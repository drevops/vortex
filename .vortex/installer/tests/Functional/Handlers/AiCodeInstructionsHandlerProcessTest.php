<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AiCodeInstructions::class)]
class AiCodeInstructionsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'ai instructions, enabled' => [
        static::cw(fn() => Env::put(AiCodeInstructions::envName(), Env::TRUE)),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileExists(static::$sut . '/AGENTS.md');
          $test->assertFileExists(static::$sut . '/CLAUDE.md');
        }),
      ],

      'ai instructions, disabled' => [
        static::cw(fn() => Env::put(AiCodeInstructions::envName(), Env::FALSE)),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/AGENTS.md');
          $test->assertFileDoesNotExist(static::$sut . '/CLAUDE.md');
        }),
      ],
    ];
  }

}
