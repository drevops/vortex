<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class DocsHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'preserve_docs_project_enabled' => [
      self::cw(fn($test): true => $test->prompts['preserve_docs_project'] = TRUE),
    ];
    yield 'preserve_docs_project_disabled' => [
      self::cw(fn($test): false => $test->prompts['preserve_docs_project'] = FALSE),
    ];
  }

}
