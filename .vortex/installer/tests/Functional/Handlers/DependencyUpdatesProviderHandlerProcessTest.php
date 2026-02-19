<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DependencyUpdatesProvider::class)]
class DependencyUpdatesProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'deps updates provider, ci, gha' => [
        static::cw(fn() => Env::put(DependencyUpdatesProvider::envName(), DependencyUpdatesProvider::RENOVATEBOT_CI)),
      ],

      'deps updates provider, ci, circleci' => [
        static::cw(function (): void {
          Env::put(DependencyUpdatesProvider::envName(), DependencyUpdatesProvider::RENOVATEBOT_CI);
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
        }),
      ],

      'deps updates provider, app' => [
        static::cw(fn() => Env::put(DependencyUpdatesProvider::envName(), DependencyUpdatesProvider::RENOVATEBOT_APP)),
      ],

      'deps updates provider, none' => [
        static::cw(fn() => Env::put(DependencyUpdatesProvider::envName(), DependencyUpdatesProvider::NONE)),
      ],
    ];
  }

}
