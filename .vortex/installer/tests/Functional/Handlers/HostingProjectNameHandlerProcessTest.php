<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProjectName;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HostingProjectName::class)]
class HostingProjectNameHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'hosting_project_name___acquia' => [
      static::cw(function ($test): void {
          $test->prompts[HostingProvider::id()] = HostingProvider::ACQUIA;
          $test->prompts[HostingProjectName::id()] = 'my_custom_acquia-project';
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains([
            'VORTEX_ACQUIA_APP_NAME=my_custom_acquia-project',
          ]);
      }),
    ];
    yield 'hosting_project_name___lagoon' => [
      static::cw(function ($test): void {
          $test->prompts[HostingProvider::id()] = HostingProvider::LAGOON;
          $test->prompts[HostingProjectName::id()] = 'my_custom_lagoon-project';
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains([
            'LAGOON_PROJECT=my_custom_lagoon-project',
            '/my_custom_lagoon-project-\$\{env-name\}/',
            '/\.my_custom_lagoon-project\.au2\.amazee\.io/',
          ]);
      }),
    ];
  }

}
