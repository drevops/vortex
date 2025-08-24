<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexInstaller\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AssignAuthorPr::class)]
#[CoversClass(LabelMergeConflictsPr::class)]
class PullRequestInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'assign author PR, enabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(AssignAuthorPr::id()), Env::TRUE)),
      ],

      'assign author PR, disabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(AssignAuthorPr::id()), Env::FALSE)),
      ],

      'label merge conflicts PR, enabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(LabelMergeConflictsPr::id()), Env::TRUE)),
      ],

      'label merge conflicts PR, disabled' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(LabelMergeConflictsPr::id()), Env::FALSE)),
      ],
    ];
  }

}
