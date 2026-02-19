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
      'assign author PR, enabled' => [
        static::cw(fn() => Env::put(AssignAuthorPr::envName(), Env::TRUE)),
      ],

      'assign author PR, disabled' => [
        static::cw(fn() => Env::put(AssignAuthorPr::envName(), Env::FALSE)),
      ],

      'label merge conflicts PR, enabled' => [
        static::cw(fn() => Env::put(LabelMergeConflictsPr::envName(), Env::TRUE)),
      ],

      'label merge conflicts PR, disabled' => [
        static::cw(fn() => Env::put(LabelMergeConflictsPr::envName(), Env::FALSE)),
      ],
    ];
  }

}
