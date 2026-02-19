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
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Name::class)]
class NamesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'names' => [
        static::cw(function (): void {
          Env::put(Name::envName(), 'New hope');
          Env::put(MachineName::envName(), 'the_new_hope');
          Env::put(Org::envName(), 'Jedi Order');
          Env::put(OrgMachineName::envName(), 'the_jedi_order');
          Env::put(Domain::envName(), 'death-star.com');
          Env::put(ModulePrefix::envName(), 'the_force');
          Env::put(Theme::envName(), 'lightsaber');
        }),
      ],
    ];
  }

}
