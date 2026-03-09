<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Migration::class)]
class MigrationHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'migration_enabled' => [
        static::cw(fn() => Env::put(Migration::envName(), Env::TRUE)),
        static::cw(function (FunctionalTestCase $test): void {
          // Files and directories created by the handler.
          $test->assertFileExists(static::$sut . '/web/sites/default/settings.migration.php');
          $test->assertFileExists(static::$sut . '/scripts/custom/provision-20-migration.sh');
          $test->assertDirectoryExists(static::$sut . '/web/modules/custom/ys_migrate');

          // Composer dependencies.
          $test->assertFileContainsString(static::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileContainsString(static::$sut . '/composer.json', 'drupal/migrate_tools');

          // Token-controlled content preserved in files.
          $test->assertFileContainsString(static::$sut . '/.gitignore', 'settings.migration.php');
          $test->assertFileContainsString(static::$sut . '/docker-compose.yml', 'database2');
          $test->assertFileContainsString(static::$sut . '/.ahoy.yml', 'download-db2');
          $test->assertFileContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE');
          $test->assertFileContainsString(static::$sut . '/web/sites/default/settings.php', 'settings.migration.php');
          $test->assertFileContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'Download migration DB');
          $test->assertFileContainsString(static::$sut . '/tests/phpunit/Drupal/SettingsTestCase.php', 'DATABASE2_');
        }),
      ],

      'migration_enabled_circleci' => [
        static::cw(function (): void {
          Env::put(Migration::envName(), Env::TRUE);
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileExists(static::$sut . '/web/sites/default/settings.migration.php');
          $test->assertFileExists(static::$sut . '/scripts/custom/provision-20-migration.sh');
          $test->assertDirectoryExists(static::$sut . '/web/modules/custom/ys_migrate');
          $test->assertFileContainsString(static::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileContainsString(static::$sut . '/composer.json', 'drupal/migrate_tools');
          $test->assertFileContainsString(static::$sut . '/.circleci/config.yml', 'Download migration DB');
        }),
      ],

      'migration_disabled' => [
        static::cw(fn() => Env::put(Migration::envName(), Env::FALSE)),
        static::cw(function (FunctionalTestCase $test): void {
          // Files and directories removed by the handler.
          $test->assertFileDoesNotExist(static::$sut . '/web/sites/default/settings.migration.php');
          $test->assertFileDoesNotExist(static::$sut . '/scripts/custom/provision-20-migration.sh');
          $test->assertDirectoryDoesNotExist(static::$sut . '/web/modules/custom/ys_migrate');

          // Composer dependencies removed.
          $test->assertFileNotContainsString(static::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileNotContainsString(static::$sut . '/composer.json', 'drupal/migrate_tools');

          // Token-controlled content removed from files.
          $test->assertFileNotContainsString(static::$sut . '/.gitignore', 'settings.migration.php');
          $test->assertFileNotContainsString(static::$sut . '/docker-compose.yml', 'database2');
          $test->assertFileNotContainsString(static::$sut . '/.ahoy.yml', 'download-db2');
          $test->assertFileNotContainsString(static::$sut . '/.env', 'VORTEX_DOWNLOAD_DB2_SOURCE');
          $test->assertFileNotContainsString(static::$sut . '/web/sites/default/settings.php', 'settings.migration.php');
          $test->assertFileNotContainsString(static::$sut . '/.github/workflows/build-test-deploy.yml', 'Download migration DB');
          $test->assertFileNotContainsString(static::$sut . '/tests/phpunit/Drupal/SettingsTestCase.php', 'DATABASE2_');
        }),
      ],

      'migration_disabled_circleci' => [
        static::cw(function (): void {
          Env::put(Migration::envName(), Env::FALSE);
          Env::put(CiProvider::envName(), CiProvider::CIRCLECI);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/web/sites/default/settings.migration.php');
          $test->assertFileDoesNotExist(static::$sut . '/scripts/custom/provision-20-migration.sh');
          $test->assertDirectoryDoesNotExist(static::$sut . '/web/modules/custom/ys_migrate');
          $test->assertFileNotContainsString(static::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileNotContainsString(static::$sut . '/composer.json', 'drupal/migrate_tools');
          $test->assertFileNotContainsString(static::$sut . '/.circleci/config.yml', 'Download migration DB');
        }),
      ],

      'migration_enabled_lagoon' => [
        static::cw(function (): void {
          Env::put(Migration::envName(), Env::TRUE);
          Env::put(HostingProvider::envName(), HostingProvider::LAGOON);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileExists(static::$sut . '/web/sites/default/settings.migration.php');
          $test->assertFileExists(static::$sut . '/scripts/custom/provision-20-migration.sh');
          $test->assertDirectoryExists(static::$sut . '/web/modules/custom/ys_migrate');
          $test->assertFileContainsString(static::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileContainsString(static::$sut . '/composer.json', 'drupal/migrate_tools');
          $test->assertFileContainsString(static::$sut . '/.lagoon.yml', 'Download migration database');
        }),
      ],

      'migration_disabled_lagoon' => [
        static::cw(function (): void {
          Env::put(Migration::envName(), Env::FALSE);
          Env::put(HostingProvider::envName(), HostingProvider::LAGOON);
        }),
        static::cw(function (FunctionalTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/web/sites/default/settings.migration.php');
          $test->assertFileDoesNotExist(static::$sut . '/scripts/custom/provision-20-migration.sh');
          $test->assertDirectoryDoesNotExist(static::$sut . '/web/modules/custom/ys_migrate');
          $test->assertFileNotContainsString(static::$sut . '/composer.json', 'drupal/migrate_plus');
          $test->assertFileNotContainsString(static::$sut . '/composer.json', 'drupal/migrate_tools');
          $test->assertFileNotContainsString(static::$sut . '/.lagoon.yml', 'Download migration database');
        }),
      ],
    ];
  }

}
