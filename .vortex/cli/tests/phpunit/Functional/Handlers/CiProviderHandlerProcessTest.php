<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class CiProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'ciprovider_gha' => [
      self::cw(function ($test): void {
          $test->prompts['ci_provider'] = 'gha';
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileNotContainsString(self::$sut . '/.github/workflows/build-test-deploy.yml', '1.x');
          $test->assertFileNotContainsString(self::$sut . '/.github/workflows/build-test-deploy.yml', '2.x');
      }),
    ];
    yield 'ciprovider_circleci' => [
      self::cw(function ($test): void {
          $test->prompts['ci_provider'] = 'circleci';
          $test->prompts['ai_code_instructions'] = TRUE;
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileNotContainsString(self::$sut . '/.circleci/config.yml', '1.x');
          $test->assertFileNotContainsString(self::$sut . '/.circleci/config.yml', '2.x');
      }),
    ];
  }

}
