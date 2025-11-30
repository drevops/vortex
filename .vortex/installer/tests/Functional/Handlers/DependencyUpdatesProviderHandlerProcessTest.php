<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DependencyUpdatesProvider::class)]
class DependencyUpdatesProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderInstall(): array {
    return [
      'deps updates provider, ci, gha' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::RENOVATEBOT_CI)),
      ],

      'deps updates provider, ci, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::RENOVATEBOT_CI);
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
      ],

      'deps updates provider, app' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::RENOVATEBOT_APP)),
      ],

      'deps updates provider, none' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DependencyUpdatesProvider::id()), DependencyUpdatesProvider::NONE)),
      ],
    ];
  }

}
