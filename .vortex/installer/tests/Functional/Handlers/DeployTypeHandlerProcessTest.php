<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeployTypes::class)]
class DeployTypeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'deploy_types_artifact' => [
      static::cw(fn($test): array => $test->prompts[DeployTypes::id()] = [DeployTypes::ARTIFACT]),
    ];
    yield 'deploy_types_lagoon' => [
      static::cw(fn($test): array => $test->prompts[DeployTypes::id()] = [DeployTypes::LAGOON]),
    ];
    yield 'deploy_types_container_image' => [
      static::cw(fn($test): array => $test->prompts[DeployTypes::id()] = [DeployTypes::CONTAINER_IMAGE]),
    ];
    yield 'deploy_types_webhook' => [
      static::cw(fn($test): array => $test->prompts[DeployTypes::id()] = [DeployTypes::WEBHOOK]),
    ];
    yield 'deploy_types_all_gha' => [
      static::cw(fn($test): array => $test->prompts[DeployTypes::id()] = [DeployTypes::WEBHOOK, DeployTypes::CONTAINER_IMAGE, DeployTypes::LAGOON, DeployTypes::ARTIFACT]),
    ];
    yield 'deploy_types_all_circleci' => [
      static::cw(function ($test): void {
          $test->prompts[DeployTypes::id()] = [DeployTypes::WEBHOOK, DeployTypes::CONTAINER_IMAGE, DeployTypes::LAGOON, DeployTypes::ARTIFACT];
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
    ];
    yield 'deploy_types_none_gha' => [
      static::cw(fn($test): array => $test->prompts[DeployTypes::id()] = []),
    ];
    yield 'deploy_types_none_circleci' => [
      static::cw(function ($test): void {
          $test->prompts[DeployTypes::id()] = [];
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
    ];
  }

}
