<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CiProvider::class)]
class CiProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderInstall(): array {
    return [
      'ciprovider, gha' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],

      'ciprovider, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],
    ];
  }

}
