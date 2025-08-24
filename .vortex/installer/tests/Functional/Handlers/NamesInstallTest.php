<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use DrevOps\VortexInstaller\Prompts\Handlers\MachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexInstaller\Prompts\Handlers\Name;
use DrevOps\VortexInstaller\Prompts\Handlers\Org;
use DrevOps\VortexInstaller\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Name::class)]
class NamesInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'names' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Name::id()), 'New hope');
          Env::put(PromptManager::makeEnvName(MachineName::id()), 'the_new_hope');
          Env::put(PromptManager::makeEnvName(Org::id()), 'Jedi Order');
          Env::put(PromptManager::makeEnvName(OrgMachineName::id()), 'the_jedi_order');
          Env::put(PromptManager::makeEnvName(Domain::id()), 'death-star.com');
          Env::put(PromptManager::makeEnvName(ModulePrefix::id()), 'the_force');
          Env::put(PromptManager::makeEnvName(Theme::id()), 'lightsaber');
        }),
      ],
    ];
  }

}
