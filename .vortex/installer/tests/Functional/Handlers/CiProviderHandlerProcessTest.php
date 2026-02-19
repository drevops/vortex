<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CiProvider::class)]
class CiProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'ciprovider, gha' => [
        static::cw(function (): void {
          Env::put(CiProvider::envName(), CiProvider::GITHUB_ACTIONS);
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', '1.x');
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', '2.x');
        }),
      ],

      'ciprovider, circleci' => [
        static::cw(function (): void {
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
          Env::put(AiCodeInstructions::envName(), Env::TRUE);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileNotContainsString(static::$sut . '/.circleci/config.yml', '1.x');
          $test->assertFileNotContainsString(static::$sut . '/.circleci/config.yml', '2.x');
        }),
      ],
    ];
  }

}
