<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class DependencyUpdatesProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'deps_updates_provider_ci_gha' => [
      self::cw(fn($test): string => $test->prompts['dependency_updates_provider'] = 'renovatebot_ci'),
    ];
    yield 'deps_updates_provider_ci_circleci' => [
      self::cw(function ($test): void {
          $test->prompts['dependency_updates_provider'] = 'renovatebot_ci';
          $test->prompts['ci_provider'] = 'circleci';
      }),
    ];
    yield 'deps_updates_provider_app' => [
      self::cw(fn($test): string => $test->prompts['dependency_updates_provider'] = 'renovatebot_app'),
    ];
    yield 'deps_updates_provider_none' => [
      self::cw(fn($test): string => $test->prompts['dependency_updates_provider'] = 'none'),
    ];
  }

}
