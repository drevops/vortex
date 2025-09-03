<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployType;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeployType::class)]
class DeployTypeInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'deploy type, artifact' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::ARTIFACT], ',', TRUE))),
      ],

      'deploy type, lagoon' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::LAGOON], ',', TRUE))),
      ],

      'deploy type, container_image' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::CONTAINER_IMAGE], ',', TRUE))),
      ],

      'deploy type, webhook' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::WEBHOOK], ',', TRUE))),
      ],

      'deploy type, all, gha' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::WEBHOOK, DeployType::CONTAINER_IMAGE, DeployType::LAGOON, DeployType::ARTIFACT]))),
      ],

      'deploy type, all, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(DeployType::id()), Converter::toList([DeployType::WEBHOOK, DeployType::CONTAINER_IMAGE, DeployType::LAGOON, DeployType::ARTIFACT]));
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
      ],

      'deploy type, none, gha' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(DeployType::id()), ',')),
      ],

      'deploy type, none, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(DeployType::id()), ',');
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
      ],
    ];
  }

}
