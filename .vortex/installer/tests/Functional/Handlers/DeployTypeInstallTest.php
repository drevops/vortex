<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeployTypes::class)]
class DeployTypeInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'deploy types, artifact' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployTypes::id()), Converter::toList([DeployTypes::ARTIFACT], ',', TRUE))),
      ],

      'deploy types, lagoon' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployTypes::id()), Converter::toList([DeployTypes::LAGOON], ',', TRUE))),
      ],

      'deploy types, container_image' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployTypes::id()), Converter::toList([DeployTypes::CONTAINER_IMAGE], ',', TRUE))),
      ],

      'deploy types, webhook' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployTypes::id()), Converter::toList([DeployTypes::WEBHOOK], ',', TRUE))),
      ],

      'deploy types, all, gha' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployTypes::id()), Converter::toList([DeployTypes::WEBHOOK, DeployTypes::CONTAINER_IMAGE, DeployTypes::LAGOON, DeployTypes::ARTIFACT]))),
      ],

      'deploy types, all, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(DeployTypes::id()), Converter::toList([DeployTypes::WEBHOOK, DeployTypes::CONTAINER_IMAGE, DeployTypes::LAGOON, DeployTypes::ARTIFACT]));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
      ],

      'deploy types, none, gha' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployTypes::id()), ',')),
      ],

      'deploy types, none, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(DeployTypes::id()), ',');
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
      ],
    ];
  }

}
