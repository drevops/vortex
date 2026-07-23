<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class CodeProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'code_provider_github' => [
      self::cw(fn($test): string => $test->prompts['code_provider'] = 'github'),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(self::$sut . '/.github/PULL_REQUEST_TEMPLATE.dist.md');
          $test->assertFileContainsString(self::$sut . '/.github/PULL_REQUEST_TEMPLATE.md', 'Checklist before requesting a review');
          $test->assertFileContainsString(self::$sut . '/renovate.json', 'github-actions');
      }),
    ];
    yield 'code_provider_other' => [
      self::cw(fn($test): string => $test->prompts['code_provider'] = 'other'),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertDirectoryDoesNotExist(self::$sut . '/.github');
          $test->assertFileNotContainsString(self::$sut . '/renovate.json', 'github-actions');
      }),
    ];
  }

}
