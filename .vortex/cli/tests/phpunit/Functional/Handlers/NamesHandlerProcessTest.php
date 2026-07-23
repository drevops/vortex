<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class NamesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'names' => [
      self::cw(function ($test): void {
        $test->prompts['name'] = 'New hope';
        $test->prompts['machine_name'] = 'the_new_hope';
        $test->prompts['org'] = 'Jedi Order';
        $test->prompts['org_machine_name'] = 'the_jedi_order';
        $test->prompts['domain'] = 'death-star.com';
        $test->prompts['module_prefix'] = 'the_force';
        $test->prompts['theme'] = 'custom';
        $test->prompts['theme_custom'] = 'lightsaber';
      }),
    ];
  }

}
