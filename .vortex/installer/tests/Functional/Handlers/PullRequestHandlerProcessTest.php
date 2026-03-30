<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexInstaller\Prompts\Handlers\LabelMergeConflictsPr;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AssignAuthorPr::class)]
#[CoversClass(LabelMergeConflictsPr::class)]
class PullRequestHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'assign_author_pr_enabled' => [
      static::cw(fn($test): true => $test->prompts[AssignAuthorPr::id()] = TRUE),
    ];
    yield 'assign_author_pr_disabled' => [
      static::cw(fn($test): false => $test->prompts[AssignAuthorPr::id()] = FALSE),
    ];
    yield 'label_merge_conflicts_pr_enabled' => [
      static::cw(fn($test): true => $test->prompts[LabelMergeConflictsPr::id()] = TRUE),
    ];
    yield 'label_merge_conflicts_pr_disabled' => [
      static::cw(fn($test): false => $test->prompts[LabelMergeConflictsPr::id()] = FALSE),
    ];
  }

}
