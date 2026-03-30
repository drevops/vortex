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
use DrevOps\VortexInstaller\Prompts\Handlers\ThemeCustom;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Name::class)]
class NamesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'names' => [
      static::cw(function ($test): void {
        $test->prompts[Name::id()] = 'New hope';
        $test->prompts[MachineName::id()] = 'the_new_hope';
        $test->prompts[Org::id()] = 'Jedi Order';
        $test->prompts[OrgMachineName::id()] = 'the_jedi_order';
        $test->prompts[Domain::id()] = 'death-star.com';
        $test->prompts[ModulePrefix::id()] = 'the_force';
        $test->prompts[Theme::id()] = Theme::CUSTOM;
        $test->prompts[ThemeCustom::id()] = 'lightsaber';
      }),
    ];
  }

}
