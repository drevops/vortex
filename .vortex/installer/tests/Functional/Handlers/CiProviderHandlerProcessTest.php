<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CiProvider::class)]
class CiProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'ciprovider_gha' => [
      static::cw(function ($test): void {
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', '1.x');
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', '2.x');
      }),
    ];
    yield 'ciprovider_circleci' => [
      static::cw(function ($test): void {
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
          $test->prompts[AiCodeInstructions::id()] = TRUE;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileNotContainsString(static::$sut . '/.circleci/config.yml', '1.x');
          $test->assertFileNotContainsString(static::$sut . '/.circleci/config.yml', '2.x');
      }),
    ];
  }

}
