<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AiCodeInstructions::class)]
class AiCodeInstructionsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'ai instructions, claude' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE)),
      ],

      'ai instructions, none' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::NONE)),
      ],
    ];
  }

}
