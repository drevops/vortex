<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesProvider;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DependencyUpdatesProvider::class)]
class DependencyUpdatesProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'deps_updates_provider_ci_gha' => [
      static::cw(fn($test): string => $test->prompts[DependencyUpdatesProvider::id()] = DependencyUpdatesProvider::RENOVATEBOT_CI),
    ];
    yield 'deps_updates_provider_ci_circleci' => [
      static::cw(function ($test): void {
          $test->prompts[DependencyUpdatesProvider::id()] = DependencyUpdatesProvider::RENOVATEBOT_CI;
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
    ];
    yield 'deps_updates_provider_app' => [
      static::cw(fn($test): string => $test->prompts[DependencyUpdatesProvider::id()] = DependencyUpdatesProvider::RENOVATEBOT_APP),
    ];
    yield 'deps_updates_provider_none' => [
      static::cw(fn($test): string => $test->prompts[DependencyUpdatesProvider::id()] = DependencyUpdatesProvider::NONE),
    ];
  }

}
