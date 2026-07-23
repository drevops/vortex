<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class MigrationHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'migration_enabled' => [
      self::cw(fn($test): true => $test->prompts['migration'] = TRUE),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          // Files and directories created by the handler.
          $test->assertFileExists(self::$sut . '/web/sites/default/settings.migration.php');
          $test->assertFileExists(self::$sut . '/web/modules/custom/ys_migrate/src/Plugin/DeployStep/MigrateContentDeployStep.php');
          $test->assertDirectoryExists(self::$sut . '/web/modules/custom/ys_migrate');

          // Composer dependencies.
          $test->assertFileContainsString(self::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileContainsString(self::$sut . '/composer.json', 'drupal/migrate_tools');

          // Token-controlled content preserved in files.
          $test->assertFileContainsString(self::$sut . '/docker-compose.yml', 'database2');
          $test->assertFileContainsString(self::$sut . '/.ahoy.yml', 'fetch-db2');
          $test->assertFileContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE');
          $test->assertFileContainsString(self::$sut . '/web/sites/default/settings.php', 'settings.migration.php');
          $test->assertFileContainsString(self::$sut . '/.github/workflows/build-test-deploy.yml', 'Fetch migration DB');
          $test->assertFileContainsString(self::$sut . '/tests/phpunit/Drupal/SettingsTestCase.php', 'DATABASE2_');
      }),
    ];
    yield 'migration_enabled_circleci' => [
      self::cw(function ($test): void {
          $test->prompts['migration'] = TRUE;
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileExists(self::$sut . '/web/sites/default/settings.migration.php');
          $test->assertFileExists(self::$sut . '/web/modules/custom/ys_migrate/src/Plugin/DeployStep/MigrateContentDeployStep.php');
          $test->assertDirectoryExists(self::$sut . '/web/modules/custom/ys_migrate');
          $test->assertFileContainsString(self::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileContainsString(self::$sut . '/composer.json', 'drupal/migrate_tools');
          $test->assertFileContainsString(self::$sut . '/.circleci/config.yml', 'Fetch migration DB');
      }),
    ];
    yield 'migration_disabled' => [
      self::cw(fn($test): false => $test->prompts['migration'] = FALSE),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          // Files and directories removed by the handler.
          $test->assertFileDoesNotExist(self::$sut . '/web/sites/default/settings.migration.php');
          $test->assertDirectoryDoesNotExist(self::$sut . '/web/modules/custom/ys_migrate');

          // Composer dependencies removed.
          $test->assertFileNotContainsString(self::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileNotContainsString(self::$sut . '/composer.json', 'drupal/migrate_tools');

          // Token-controlled content removed from files.
          $test->assertFileNotContainsString(self::$sut . '/docker-compose.yml', 'database2');
          $test->assertFileNotContainsString(self::$sut . '/.ahoy.yml', 'fetch-db2');
          $test->assertFileNotContainsString(self::$sut . '/.env', 'VORTEX_FETCH_DB2_SOURCE');
          $test->assertFileNotContainsString(self::$sut . '/web/sites/default/settings.php', 'settings.migration.php');
          $test->assertFileNotContainsString(self::$sut . '/.github/workflows/build-test-deploy.yml', 'Fetch migration DB');
          $test->assertFileNotContainsString(self::$sut . '/tests/phpunit/Drupal/SettingsTestCase.php', 'DATABASE2_');
      }),
    ];
    yield 'migration_disabled_circleci' => [
      self::cw(function ($test): void {
          $test->prompts['migration'] = FALSE;
          $test->prompts['ci_provider'] = 'circleci';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(self::$sut . '/web/sites/default/settings.migration.php');
          $test->assertDirectoryDoesNotExist(self::$sut . '/web/modules/custom/ys_migrate');
          $test->assertFileNotContainsString(self::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileNotContainsString(self::$sut . '/composer.json', 'drupal/migrate_tools');
          $test->assertFileNotContainsString(self::$sut . '/.circleci/config.yml', 'Fetch migration DB');
      }),
    ];
    yield 'migration_enabled_lagoon' => [
      self::cw(function ($test): void {
          $test->prompts['migration'] = TRUE;
          $test->prompts['hosting_provider'] = 'lagoon';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileExists(self::$sut . '/web/sites/default/settings.migration.php');
          $test->assertFileExists(self::$sut . '/web/modules/custom/ys_migrate/src/Plugin/DeployStep/MigrateContentDeployStep.php');
          $test->assertDirectoryExists(self::$sut . '/web/modules/custom/ys_migrate');
          $test->assertFileContainsString(self::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileContainsString(self::$sut . '/composer.json', 'drupal/migrate_tools');
          $test->assertFileContainsString(self::$sut . '/.lagoon.yml', 'Download migration database');
      }),
    ];
    yield 'migration_disabled_lagoon' => [
      self::cw(function ($test): void {
          $test->prompts['migration'] = FALSE;
          $test->prompts['hosting_provider'] = 'lagoon';
      }),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(self::$sut . '/web/sites/default/settings.migration.php');
          $test->assertDirectoryDoesNotExist(self::$sut . '/web/modules/custom/ys_migrate');
          $test->assertFileNotContainsString(self::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileNotContainsString(self::$sut . '/composer.json', 'drupal/migrate_tools');
          $test->assertFileNotContainsString(self::$sut . '/.lagoon.yml', 'Download migration database');
      }),
    ];
  }

}
