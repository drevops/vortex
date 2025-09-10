<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;
use DrevOps\Vortex\Tests\Traits\LoggerTrait;

/**
 * Provides environment and configuration testing steps.
 */
trait StepEnvironmentTrait {

  use LoggerTrait;

  protected function stepGitignore(): void {
    $this->logStepStart();

    $this->logSubstep('Testing git tracking behavior');
    $this->processRun('git status --porcelain');
    $this->assertProcessSuccessful();

    $this->logSubstep('Assert scaffold files are tracked');
    $this->assertFileExists('.gitignore', 'Gitignore file should exist');

    $this->logStepFinish();
  }

  protected function stepEnvChanges(): void {
    $this->logStepStart();

    // Assert that .env does not contain test values.
    $this->assertFileNotContainsString('MY_CUSTOM_VAR', '.env');
    $this->assertFileNotContainsString('my_custom_var_value', '.env');

    // Assert that test variable is not available inside of containers.
    $this->processRun('ahoy cli "printenv | grep -q MY_CUSTOM_VAR"');
    $this->assertProcessFailed();

    // Assert that test value is not available inside of containers.
    $this->processRun('ahoy cli \'echo $MY_CUSTOM_VAR | grep -q my_custom_var_value\'');
    $this->assertProcessFailed();
    $this->assertProcessOutputNotContains('my_custom_var_value');

    // Add variable to the .env file and apply the change to container.
    $this->addVarToFile('.env', 'MY_CUSTOM_VAR', 'my_custom_var_value');
    $this->processRun('ahoy up cli');
    $this->syncToContainer();

    // Assert that .env contains test values.
    $this->assertFileContainsString('MY_CUSTOM_VAR', '.env');
    $this->assertFileContainsString('my_custom_var_value', '.env');

    // Assert that test variable and values are available inside of containers.
    $this->processRun('ahoy cli "printenv | grep MY_CUSTOM_VAR"');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('my_custom_var_value');

    // Assert that test variable and value are available inside of containers.
    $this->processRun('ahoy cli \'echo $MY_CUSTOM_VAR | grep my_custom_var_value\'');
    $this->assertProcessOutputContains('my_custom_var_value');
    $this->assertProcessSuccessful();

    // Restore file, apply changes and assert that original behaviour has been restored.
    $this->restoreFile('.env');
    $this->processRun('ahoy up cli');
    $this->syncToContainer();

    $this->assertFileNotContainsString('MY_CUSTOM_VAR', '.env');
    $this->assertFileNotContainsString('my_custom_var_value', '.env');
    $this->processRun('ahoy cli "printenv | grep -q MY_CUSTOM_VAR"');
    $this->assertProcessFailed();
    $this->processRun('ahoy cli \'echo $MY_CUSTOM_VAR | grep my_custom_var_value\'');
    $this->assertProcessFailed();
    $this->assertProcessOutputNotContains('my_custom_var_value');

    $this->logStepFinish();
  }

  protected function stepTimezone(): void {
    $this->logStepStart();

    // Assert that .env contains a default value.
    // Note that AEDT changes to AEST during winter.
    $this->assertFileContainsString('TZ=UTC', '.env');
    $this->processRun('docker compose exec cli date');
    $this->assertProcessOutputContains('UTC');
    $this->processRun('docker compose exec php date');
    $this->assertProcessOutputContains('UTC');
    $this->processRun('docker compose exec nginx date');
    $this->assertProcessOutputContains('UTC');
    $this->processRun('docker compose exec database date');
    $this->assertProcessOutputContains('UTC');

    // Add variable to the .env file and apply the change to container.
    $this->addVarToFile('.env', 'TZ', '"Australia/Perth"');
    $this->syncToContainer();
    $this->processRun('ahoy up');

    $this->processRun('docker compose exec cli date');
    $this->assertProcessOutputContains('AWST');
    $this->processRun('docker compose exec php date');
    $this->assertProcessOutputContains('AWST');
    $this->processRun('docker compose exec nginx date');
    $this->assertProcessOutputContains('AWST');
    $this->processRun('docker compose exec database date');
    $this->assertProcessOutputContains('AWST');

    // Restore file, apply changes and assert that original behaviour has been restored.
    $this->restoreFile('.env');
    $this->syncToContainer();
    $this->processRun('ahoy up');
    sleep(10);

    $this->logStepFinish();
  }

  protected function stepAhoyDebug(): void {
    $this->logStepStart();

    $this->logSubstep('Enable debug');
    // Assert that Xdebug is disabled by default from the inside of the container.
    $this->processRun('ahoy cli "php -v | grep Xdebug"');
    $this->assertProcessFailed();

    // Assert info correctly shown from the outside of the container.
    $this->processRun('ahoy info');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Xdebug');
    $this->assertProcessOutputContains('Disabled');
    $this->assertProcessOutputNotContains('Enabled');

    // Enable debugging.
    $this->processRun('ahoy debug');
    $this->assertProcessSuccessful();
    // Assert that the stack has restarted.
    // Using "reat" from "Create" or "Creating".
    $this->assertProcessErrorOutputContains('reat');
    $this->assertProcessOutputContains('Enabled debug');

    // Assert that Xdebug is enabled from the inside of the container.
    $this->processRun('ahoy cli "php -v | grep Xdebug"');
    $this->assertProcessSuccessful();

    // Assert info correctly shown from the outside of the container.
    $this->processRun('ahoy info');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputNotContains('Disabled');
    $this->assertProcessOutputContains('Enabled');

    // Assert that command when debugging is enabled does not restart the stack.
    $this->processRun('ahoy debug');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Enabled debug');

    $this->logSubstep('Disable debug');
    // Assert that restarting the stack does not have Xdebug enabled.
    $this->processRun('ahoy up');
    $this->assertProcessSuccessful();

    // Assert that Xdebug is disabled from the inside of the container.
    $this->processRun('ahoy cli "php -v | grep Xdebug"');
    $this->assertProcessFailed();

    // Assert info correctly shown from the outside of the container.
    $this->processRun('ahoy info');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Xdebug');
    $this->assertProcessOutputContains('Disabled');
    $this->assertProcessOutputNotContains('Enabled');

    $this->logStepFinish();
  }

  protected function stepAhoyReset(string $webroot = 'web'): void {
    $this->logStepStart();

    File::dump('untracked_file.txt', 'test content');
    $this->assertFileExists('untracked_file.txt');

    $this->assertFileExists('.idea/idea_file.txt');

    $this->createDevelopmentSettings($webroot);

    File::mkdir('.logs/screenshots');
    $this->assertDirectoryExists('.logs/screenshots');

    $this->processRun('ahoy reset');
    $this->assertProcessSuccessful();
    sleep(10);

    // Assert that initial Vortex files have not been removed.
    $this->assertCommonFilesPresent($webroot);
    $this->assertFilesPresent($webroot);

    $this->assertDirectoryDoesNotExist($webroot . '/modules/contrib');
    $this->assertDirectoryDoesNotExist($webroot . '/themes/contrib');
    $this->assertDirectoryDoesNotExist('vendor');
    $this->assertDirectoryDoesNotExist($webroot . '/themes/custom/star_wars/node_modules');
    $this->assertDirectoryExists('.logs/screenshots');

    // Assert manually created local settings file exists.
    $this->assertFileExists($webroot . '/sites/default/settings.local.php');
    // Assert manually created local services file exists.
    $this->assertFileExists($webroot . '/sites/default/services.local.yml');
    // Assert manually created file still exists.
    $this->assertFileExists('untracked_file.txt');
    // Assert IDE config file still exists.
    $this->assertFileExists('.idea/idea_file.txt');

    $this->assertGitRepo();

    $this->removeDevelopmentSettings($webroot);

    $this->logStepFinish();
  }

  protected function stepAhoyResetHard(string $webroot = 'web'): void {
    $this->logStepStart();

    File::dump('untracked_file.txt', 'test content');
    $this->assertFileExists('untracked_file.txt');

    $this->assertFileExists('.idea/idea_file.txt');

    $this->createDevelopmentSettings($webroot);

    File::mkdir('.logs/screenshots');
    $this->assertDirectoryExists('.logs/screenshots');

    $this->processRun('ahoy reset hard');
    $this->assertProcessSuccessful();
    sleep(10);

    $this->assertCommonFilesPresent($webroot);
    $this->assertFilesPresent($webroot);

    $this->assertFileDoesNotExist($webroot . '/sites/default/settings.local.php');
    $this->assertFileDoesNotExist($webroot . '/sites/default/services.local.yml');

    // Assert manually created file still exists.
    $this->assertFileDoesNotExist('untracked_file.txt');
    // Assert IDE config file still exists.
    $this->assertFileExists('.idea/idea_file.txt');

    $this->assertDirectoryDoesNotExist('.logs/screenshots');

    $this->assertGitRepo();

    $this->removeDevelopmentSettings($webroot);

    $this->logStepFinish();
  }

  protected function addVarToFile(string $file, string $var, string $value): void {
    // Backup original file first
    $this->backupFile($file);
    $content = File::read($file);
    $content .= sprintf('%s%s=%s%s', PHP_EOL, $var, $value, PHP_EOL);
    File::dump($file, $content);
  }

  protected function backupFile(string $file): void {
    $backup_dir = '/tmp/bkp';
    if (!is_dir($backup_dir)) {
      mkdir($backup_dir, 0755, TRUE);
    }
    File::copy($file, $backup_dir . '/' . basename($file));
  }

  protected function restoreFile(string $file): void {
    $backup_file = '/tmp/bkp/' . basename($file);
    if (file_exists($backup_file)) {
      File::copy($backup_file, $file);
    }
  }

  protected function createDevelopmentSettings(string $webroot = 'web'): void {
    File::copy($webroot . '/sites/default/example.settings.local.php', $webroot . '/sites/default/settings.local.php');
    // Assert manually created local settings file exists.
    $this->assertFileExists($webroot . '/sites/default/settings.local.php');

    File::copy($webroot . '/sites/default/example.services.local.yml', $webroot . '/sites/default/services.local.yml');
    // Assert manually created local services file exists.
    $this->assertFileExists($webroot . '/sites/default/services.local.yml');
  }

  protected function removeDevelopmentSettings(string $webroot = 'web'): void {
    File::remove($webroot . '/sites/default/settings.local.php');
    $this->assertFileDoesNotExist($webroot . '/sites/default/settings.local.php');
    File::remove($webroot . '/sites/default/services.local.yml');
    $this->assertFileDoesNotExist($webroot . '/sites/default/services.local.yml');
  }

  protected function assertFilesPresent(string $webroot): void {
    // Use existing method from base class with correct signature
    $this->assertCommonFilesPresent($webroot);
  }

  protected function assertGitRepo(): void {
    $this->assertDirectoryExists('.git');
  }

}
