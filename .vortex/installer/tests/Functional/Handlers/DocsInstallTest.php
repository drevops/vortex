<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsOnboarding;
use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PreserveDocsProject::class)]
#[CoversClass(PreserveDocsOnboarding::class)]
class DocsInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'preserve docs project, enabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsProject::id()), Env::TRUE)),
      ],

      'preserve docs project, disabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsProject::id()), Env::FALSE)),
      ],

      'preserve docs onboarding, enabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsOnboarding::id()), Env::TRUE)),
      ],

      'preserve docs onboarding, disabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsOnboarding::id()), Env::FALSE)),
      ],
    ];
  }

}
