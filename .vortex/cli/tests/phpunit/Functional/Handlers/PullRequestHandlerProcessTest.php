<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class PullRequestHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'assign_author_pr_enabled' => [
      self::cw(fn($test): true => $test->prompts['assign_author_pr'] = TRUE),
    ];
    yield 'assign_author_pr_disabled' => [
      self::cw(fn($test): false => $test->prompts['assign_author_pr'] = FALSE),
    ];
    yield 'label_merge_conflicts_pr_enabled' => [
      self::cw(fn($test): true => $test->prompts['label_merge_conflicts_pr'] = TRUE),
    ];
    yield 'label_merge_conflicts_pr_disabled' => [
      self::cw(fn($test): false => $test->prompts['label_merge_conflicts_pr'] = FALSE),
    ];
  }

}
