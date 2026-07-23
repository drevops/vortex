<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class HostingProjectNameHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'hosting_project_name___acquia' => [
      self::cw(function ($test): void {
          $test->prompts['hosting_provider'] = 'acquia';
          $test->prompts['hosting_project_name'] = 'my_custom_acquia-project';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains([
            'VORTEX_ACQUIA_APP_NAME=my_custom_acquia-project',
          ]);
      }),
    ];
    yield 'hosting_project_name___lagoon' => [
      self::cw(function ($test): void {
          $test->prompts['hosting_provider'] = 'lagoon';
          $test->prompts['hosting_project_name'] = 'my_custom_lagoon-project';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains([
            'LAGOON_PROJECT=my_custom_lagoon-project',
            '/my_custom_lagoon-project-\$\{env-name\}/',
            '/\.my_custom_lagoon-project\.au2\.amazee\.io/',
          ]);
      }),
    ];
  }

}
