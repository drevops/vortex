<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\Domain;
use DrevOps\VortexCli\Prompts\Handlers\MachineName;
use DrevOps\VortexCli\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexCli\Prompts\Handlers\Name;
use DrevOps\VortexCli\Prompts\Handlers\Org;
use DrevOps\VortexCli\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexCli\Prompts\Handlers\Theme;
use DrevOps\VortexCli\Prompts\Handlers\ThemeCustom;
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
