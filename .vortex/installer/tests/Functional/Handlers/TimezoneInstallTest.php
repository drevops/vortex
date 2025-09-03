<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Timezone;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Timezone::class)]
class TimezoneInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [

      'timezone, gha' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Timezone::id()), 'America/New_York');
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::GITHUB_ACTIONS);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          // Timezone should be replaced in .env file.
          $test->assertFileContainsString('TZ=America/New_York', static::$sut . '/.env');
          $test->assertFileNotContainsString('UTC', static::$sut . '/.env');

          // Timezone should be replaced in Renovate config.
          $test->assertFileContainsString('"timezone": "America/New_York"', static::$sut . '/renovate.json');
          $test->assertFileNotContainsString('UTC', static::$sut . '/renovate.json');

          // Timezone should not be replaced in GHA config in code as it should
          // be overridden via UI.
          $test->assertFileNotContainsString('America/New_York', static::$sut . '/.github/workflows/build-test-deploy.yml');
          $test->assertFileContainsString('UTC', static::$sut . '/.github/workflows/build-test-deploy.yml');

          // Timezone should not be replaced in Docker Compose config.
          $test->assertFileNotContainsString('America/New_York', static::$sut . '/docker-compose.yml');
          $test->assertFileContainsString('UTC', static::$sut . '/docker-compose.yml');
        }),
      ],

      'timezone, circleci' => [
        static::cw(function (): void {
          Env::put(PromptManager::makeEnvName(Timezone::id()), 'America/New_York');
          Env::put(PromptManager::makeEnvName(CiProvider::id()), CiProvider::CIRCLECI);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          // Timezone should not be replaced in CircleCI config in code as it
          // should be overridden via UI.
          $test->assertFileNotContainsString('TZ: America/New_York', static::$sut . '/.circleci/config.yml');
          $test->assertFileContainsString('TZ: UTC', static::$sut . '/.circleci/config.yml');
        }),
      ],
    ];
  }

}
