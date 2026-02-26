<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PreserveDocsProject::class)]
class DocsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'preserve_docs_project_enabled' => [
        static::cw(fn() => Env::put(PreserveDocsProject::envName(), Env::TRUE)),
      ],

      'preserve_docs_project_disabled' => [
        static::cw(fn() => Env::put(PreserveDocsProject::envName(), Env::FALSE)),
      ],

    ];
  }

}
