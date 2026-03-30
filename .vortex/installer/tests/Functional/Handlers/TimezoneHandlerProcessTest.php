<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Timezone;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Timezone::class)]
class TimezoneHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'timezone_gha' => [
      static::cw(function ($test): void {
          $test->prompts[Timezone::id()] = 'America/New_York';
          $test->prompts[CiProvider::id()] = CiProvider::GITHUB_ACTIONS;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          // Timezone should be replaced in .env file.
          $test->assertFileContainsString(static::$sut . '/.env', 'TZ=America/New_York');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'UTC');

          // Timezone should be replaced in Renovate config.
          $test->assertFileContainsString(static::$sut . '/renovate.json', '"timezone": "America/New_York"');
          $test->assertFileNotContainsString(static::$sut . '/renovate.json', 'UTC');

          // Timezone should not be replaced in GHA config in code as it should
          // be overridden via UI.
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'America/New_York');
          $test->assertFileContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'UTC');

          // Timezone should not be replaced in Docker Compose config.
          $test->assertFileNotContainsString(static::$sut . '/docker-compose.yml', 'America/New_York');
          $test->assertFileContainsString(static::$sut . '/docker-compose.yml', 'UTC');
      }),
    ];
    yield 'timezone_circleci' => [
      static::cw(function ($test): void {
          $test->prompts[Timezone::id()] = 'America/New_York';
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          // Timezone should not be replaced in CircleCI config in code as it
          // should be overridden via UI.
          $test->assertFileNotContainsString(static::$sut . '/.circleci/config.yml', 'TZ: America/New_York');
          $test->assertFileContainsString(static::$sut . '/.circleci/config.yml', 'TZ: UTC');
      }),
    ];
  }

}
