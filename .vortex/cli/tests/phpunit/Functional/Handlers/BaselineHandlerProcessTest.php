<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * Baseline snapshot: a default non-interactive install of the local template.
 */
#[Group('snapshot')]
#[CoversNothing]
final class BaselineHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield self::BASELINE_DATASET => [NULL, NULL, []];
  }

}
