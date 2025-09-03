<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\ProvisionType;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProvisionType::class)]
class ProvisionTypeInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'provision, database' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(ProvisionType::id()), ProvisionType::DATABASE)),
      ],

      'provision, database, lagoon' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(ProvisionType::id()), ProvisionType::DATABASE);
          Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::LAGOON);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],

      'provision, profile' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(ProvisionType::id()), ProvisionType::PROFILE);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],
    ];
  }

}
