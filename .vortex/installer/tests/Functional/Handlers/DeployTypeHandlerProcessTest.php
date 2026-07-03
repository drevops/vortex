<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeployTypes::class)]
class DeployTypeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'deploy_types_artifact' => [
      static::cw(fn($test): array => $test->prompts[DeployTypes::id()] = [DeployTypes::ARTIFACT]),
      static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('VORTEX_DEPLOY_ARTIFACT_SRC');
          $test->assertSutNotContains('VORTEX_DEPLOY_WEBHOOK_URL');
      }),
    ];
    yield 'deploy_types_lagoon' => [
      static::cw(fn($test): array => $test->prompts[DeployTypes::id()] = [DeployTypes::LAGOON]),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains(['/VORTEX_DEPLOY_ARTIFACT_/', 'VORTEX_DEPLOY_WEBHOOK_URL'])),
    ];
    yield 'deploy_types_webhook' => [
      static::cw(fn($test): array => $test->prompts[DeployTypes::id()] = [DeployTypes::WEBHOOK]),
      static::cw(function (FunctionalTestCase $test): void {
          $test->assertSutContains('VORTEX_DEPLOY_WEBHOOK_URL');
          $test->assertSutNotContains('/VORTEX_DEPLOY_ARTIFACT_/');
      }),
    ];
    yield 'deploy_types_all_gha' => [
      static::cw(fn($test): array => $test->prompts[DeployTypes::id()] = [DeployTypes::WEBHOOK, DeployTypes::LAGOON, DeployTypes::ARTIFACT]),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutContains(['VORTEX_DEPLOY_ARTIFACT_SRC', 'VORTEX_DEPLOY_WEBHOOK_URL'])),
    ];
    yield 'deploy_types_all_circleci' => [
      static::cw(function ($test): void {
          $test->prompts[DeployTypes::id()] = [DeployTypes::WEBHOOK, DeployTypes::LAGOON, DeployTypes::ARTIFACT];
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutContains('VORTEX_DEPLOY_ARTIFACT_SRC')),
    ];
    yield 'deploy_types_none_gha' => [
      static::cw(fn($test): array => $test->prompts[DeployTypes::id()] = []),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('/VORTEX_DEPLOY_/')),
    ];
    yield 'deploy_types_none_circleci' => [
      static::cw(function ($test): void {
          $test->prompts[DeployTypes::id()] = [];
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('/VORTEX_DEPLOY_/')),
    ];
  }

}
