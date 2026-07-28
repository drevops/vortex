<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\PreserveDocsProject;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PreserveDocsProject::class)]
class DocsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'preserve_docs_project_enabled' => [
      static::cw(fn($test): true => $test->prompts[PreserveDocsProject::id()] = TRUE),
    ];
    yield 'preserve_docs_project_disabled' => [
      static::cw(fn($test): false => $test->prompts[PreserveDocsProject::id()] = FALSE),
    ];
  }

}
