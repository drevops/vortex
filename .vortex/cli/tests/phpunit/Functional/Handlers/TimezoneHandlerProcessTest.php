<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class TimezoneHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'timezone_gha' => [
      self::cw(function ($test): void {
          $test->prompts['timezone'] = 'America/New_York';
          $test->prompts['ci_provider'] = 'gha';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          // Timezone should be replaced in .env file.
          $test->assertFileContainsString(self::$sut . '/.env', 'TZ=America/New_York');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'UTC');

          // Timezone should be replaced in Renovate config.
          $test->assertFileContainsString(self::$sut . '/renovate.json', '"timezone": "America/New_York"');
          $test->assertFileNotContainsString(self::$sut . '/renovate.json', 'UTC');

          // Timezone should not be replaced in GHA config in code as it should
          // be overridden via UI.
          $test->assertFileNotContainsString(self::$sut . '/.github/workflows/build-test-deploy.yml', 'America/New_York');
          $test->assertFileContainsString(self::$sut . '/.github/workflows/build-test-deploy.yml', 'UTC');

          // Timezone should not be replaced in Docker Compose config.
          $test->assertFileNotContainsString(self::$sut . '/docker-compose.yml', 'America/New_York');
          $test->assertFileContainsString(self::$sut . '/docker-compose.yml', 'UTC');
      }),
    ];
    yield 'timezone_circleci' => [
      self::cw(function ($test): void {
          $test->prompts['timezone'] = 'America/New_York';
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          // Timezone should not be replaced in CircleCI config in code as it
          // should be overridden via UI.
          $test->assertFileNotContainsString(self::$sut . '/.circleci/config.yml', 'TZ: America/New_York');
          $test->assertFileContainsString(self::$sut . '/.circleci/config.yml', 'TZ: UTC');
      }),
    ];
  }

}
