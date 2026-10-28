<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeReview;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AiCodeReview::class)]
class AiCodeReviewHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'ai_code_review_enabled' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): true => $test->prompts[AiCodeReview::id()] = TRUE),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileExists(static::$sut . '/.coderabbit.yaml');
          $test->assertFileNotContainsString(static::$sut . '/.coderabbit.yaml', '.vortex/');
      }),
    ];
    yield 'ai_code_review_disabled' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): false => $test->prompts[AiCodeReview::id()] = FALSE),
      static::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertFileDoesNotExist(static::$sut . '/.coderabbit.yaml')),
    ];
  }

}
