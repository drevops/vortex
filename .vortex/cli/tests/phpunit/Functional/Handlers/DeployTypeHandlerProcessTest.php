<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class DeployTypeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'deploy_types_artifact' => [
      self::cw(fn($test): array => $test->prompts['deploy_types'] = ['artifact']),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('VORTEX_DEPLOY_ARTIFACT_SRC');
          $test->assertSutNotContains('VORTEX_DEPLOY_WEBHOOK_URL');
      }),
    ];
    yield 'deploy_types_lagoon' => [
      self::cw(fn($test): array => $test->prompts['deploy_types'] = ['lagoon']),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains(['/VORTEX_DEPLOY_ARTIFACT_/', 'VORTEX_DEPLOY_WEBHOOK_URL'])),
    ];
    yield 'deploy_types_webhook' => [
      self::cw(fn($test): array => $test->prompts['deploy_types'] = ['webhook']),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertSutContains('VORTEX_DEPLOY_WEBHOOK_URL');
          $test->assertSutNotContains('/VORTEX_DEPLOY_ARTIFACT_/');
      }),
    ];
    yield 'deploy_types_all_gha' => [
      self::cw(fn($test): array => $test->prompts['deploy_types'] = ['webhook', 'lagoon', 'artifact']),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutContains(['VORTEX_DEPLOY_ARTIFACT_SRC', 'VORTEX_DEPLOY_WEBHOOK_URL'])),
    ];
    yield 'deploy_types_all_circleci' => [
      self::cw(function ($test): void {
          $test->prompts['deploy_types'] = ['webhook', 'lagoon', 'artifact'];
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutContains('VORTEX_DEPLOY_ARTIFACT_SRC')),
    ];
    yield 'deploy_types_none_gha' => [
      self::cw(fn($test): array => $test->prompts['deploy_types'] = []),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('/VORTEX_DEPLOY_/')),
    ];
    yield 'deploy_types_none_circleci' => [
      self::cw(function ($test): void {
          $test->prompts['deploy_types'] = [];
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains(['/VORTEX_DEPLOY_/', 'acquia', 'LAGOON_PROJECT'])),
    ];
  }

}
