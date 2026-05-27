<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\VisualRegression;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(VisualRegression::class)]
class VisualRegressionHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'visual_regression_enabled' => [
      static::cw(fn($test): true => $test->prompts[VisualRegression::id()] = TRUE),
    ];
    yield 'visual_regression_disabled' => [
      static::cw(fn($test): false => $test->prompts[VisualRegression::id()] = FALSE),
    ];
  }

}
