<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeployTypes::class)]
class DeployTypeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'deploy_types_artifact' => [
      static::cw(fn() => Env::put(DeployTypes::envName(), Converter::toList([DeployTypes::ARTIFACT], ',', TRUE))),
    ];
    yield 'deploy_types_lagoon' => [
      static::cw(fn() => Env::put(DeployTypes::envName(), Converter::toList([DeployTypes::LAGOON], ',', TRUE))),
    ];
    yield 'deploy_types_container_image' => [
      static::cw(fn() => Env::put(DeployTypes::envName(), Converter::toList([DeployTypes::CONTAINER_IMAGE], ',', TRUE))),
    ];
    yield 'deploy_types_webhook' => [
      static::cw(fn() => Env::put(DeployTypes::envName(), Converter::toList([DeployTypes::WEBHOOK], ',', TRUE))),
    ];
    yield 'deploy_types_all_gha' => [
      static::cw(fn() => Env::put(DeployTypes::envName(), Converter::toList([DeployTypes::WEBHOOK, DeployTypes::CONTAINER_IMAGE, DeployTypes::LAGOON, DeployTypes::ARTIFACT]))),
    ];
    yield 'deploy_types_all_circleci' => [
      static::cw(function (): void {
          Env::put(DeployTypes::envName(), Converter::toList([DeployTypes::WEBHOOK, DeployTypes::CONTAINER_IMAGE, DeployTypes::LAGOON, DeployTypes::ARTIFACT]));
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
      }),
    ];
    yield 'deploy_types_none_gha' => [
      static::cw(fn() => Env::put(DeployTypes::envName(), ',')),
    ];
    yield 'deploy_types_none_circleci' => [
      static::cw(function (): void {
          Env::put(DeployTypes::envName(), ',');
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
      }),
    ];
  }

}
