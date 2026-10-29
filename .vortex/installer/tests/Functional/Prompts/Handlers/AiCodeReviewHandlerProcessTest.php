<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeReview;
use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
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
    yield 'ai_code_review_enabled_no_docs' => [
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->prompts[AiCodeReview::id()] = TRUE;
          $test->prompts[PreserveDocsProject::id()] = FALSE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileExists(static::$sut . '/.coderabbit.yaml');
          $test->assertFileNotContainsString(static::$sut . '/.coderabbit.yaml', 'docs/testing.md');
          $test->assertFileNotContainsString(static::$sut . '/.coderabbit.yaml', 'docs/**/*.md');
          $test->assertFileContainsString(static::$sut . '/.coderabbit.yaml', 'AGENTS.md');
      }),
    ];
    yield 'ai_code_review_disabled' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): false => $test->prompts[AiCodeReview::id()] = FALSE),
      static::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertFileDoesNotExist(static::$sut . '/.coderabbit.yaml')),
    ];
  }

}
