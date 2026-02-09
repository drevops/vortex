<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HostingProjectName::class)]
class HostingProjectNameHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'hosting project name - acquia' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::ACQUIA);
          Env::put(PromptManager::makeEnvName(HostingProjectName::id()), 'my_custom_acquia-project');
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains([
            'VORTEX_ACQUIA_APP_NAME=my_custom_acquia-project',
          ]);
        }),
      ],

      'hosting project name - lagoon' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(HostingProvider::id()), HostingProvider::LAGOON);
          Env::put(PromptManager::makeEnvName(HostingProjectName::id()), 'my_custom_lagoon-project');
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains([
            'LAGOON_PROJECT=my_custom_lagoon-project',
            '/my_custom_lagoon-project-\$\{env-name\}/',
            '/\.my_custom_lagoon-project\.au2\.amazee\.io/',
          ]);
        }),
      ],
    ];
  }

}
