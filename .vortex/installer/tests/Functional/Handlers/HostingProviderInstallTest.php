<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HostingProvider::class)]
class HostingProviderInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'hosting, acquia' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::ACQUIA);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
      ],

      'hosting, lagoon' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::LAGOON);
          Env::put(PromptManager::makeEnvName(AiCodeInstructions::id()), AiCodeInstructions::CLAUDE);
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('acquia')),
      ],
    ];
  }

}
