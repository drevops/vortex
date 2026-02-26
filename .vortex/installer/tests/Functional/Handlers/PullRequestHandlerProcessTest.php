<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexInstaller\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AssignAuthorPr::class)]
#[CoversClass(LabelMergeConflictsPr::class)]
class PullRequestHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'assign_author_pr_enabled' => [
        static::cw(fn() => Env::put(AssignAuthorPr::envName(), Env::TRUE)),
      ],

      'assign_author_pr_disabled' => [
        static::cw(fn() => Env::put(AssignAuthorPr::envName(), Env::FALSE)),
      ],

      'label_merge_conflicts_pr_enabled' => [
        static::cw(fn() => Env::put(LabelMergeConflictsPr::envName(), Env::TRUE)),
      ],

      'label_merge_conflicts_pr_disabled' => [
        static::cw(fn() => Env::put(LabelMergeConflictsPr::envName(), Env::FALSE)),
      ],
    ];
  }

}
