<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PreserveDocsProject::class)]
class DocsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderInstall(): array {
    return [
      'preserve docs project, enabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsProject::id()), Env::TRUE)),
      ],

      'preserve docs project, disabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(PreserveDocsProject::id()), Env::FALSE)),
      ],

    ];
  }

}
