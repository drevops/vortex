<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class VisualRegressionHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'visual_regression_enabled' => [
      self::cw(fn($test): true => $test->prompts['visual_regression'] = TRUE),
    ];
    yield 'visual_regression_disabled' => [
      self::cw(fn($test): false => $test->prompts['visual_regression'] = FALSE),
    ];
  }

}
