<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class CodeCoverageProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'code_coverage_provider_codecov' => [
      self::cw(fn($test): string => $test->prompts['code_coverage_provider'] = 'codecov'),
    ];
    yield 'code_coverage_provider_codecov_circleci' => [
      self::cw(function ($test): void {
          $test->prompts['code_coverage_provider'] = 'codecov';
          $test->prompts['ci_provider'] = 'circleci';
      }),
    ];
  }

}
