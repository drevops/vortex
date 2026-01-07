<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for provision script.
 */
#[Group('provision')]
#[RunTestsInSeparateProcesses]
class ProvisionTest extends UnitTestCase {

  protected string $configPath;

  protected string $dbDumpPath;

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';

    // Create directory structure.
    $this->configPath = self::$tmp . '/config/default';
    mkdir($this->configPath, 0755, TRUE);

    $this->dbDumpPath = self::$tmp . '/.data';
    mkdir($this->dbDumpPath, 0755, TRUE);

    // Create a valid config file.
    file_put_contents($this->configPath . '/system.site.yml', "uuid: 12345678-1234-1234-1234-123456789012\nname: Test Site\n");

    // Create a valid db dump file.
    file_put_contents($this->dbDumpPath . '/db.sql', 'CREATE TABLE test;');

    // Create vendor/bin/drush.
    $drush_dir = self::$tmp . '/vendor/bin';
    mkdir($drush_dir, 0755, TRUE);
    file_put_contents($drush_dir . '/drush', "#!/bin/bash\necho 'drush'\n");
    chmod($drush_dir . '/drush', 0755);

    // Set up default environment variables.
    $this->envSetMultiple([
      'VORTEX_PROVISION_SKIP' => '',
      'VORTEX_PROVISION_TYPE' => 'database',
      'VORTEX_PROVISION_OVERRIDE_DB' => '0',
      'VORTEX_PROVISION_SANITIZE_DB_SKIP' => '0',
      'VORTEX_PROVISION_USE_MAINTENANCE_MODE' => '0',
      'VORTEX_PROVISION_POST_OPERATIONS_SKIP' => '0',
      'VORTEX_PROVISION_DB' => '',
      'VORTEX_PROVISION_SCRIPTS_DIR' => self::$tmp . '/scripts/custom',
      'WEBROOT' => 'web',
      'DRUPAL_SITE_NAME' => 'Test Site',
      'DRUPAL_SITE_EMAIL' => 'test@example.com',
      'DRUPAL_PROFILE' => 'standard',
      'VORTEX_DB_DIR' => $this->dbDumpPath,
      'VORTEX_DB_FILE' => 'db.sql',
      'VORTEX_DB_IMAGE' => '',
      'DRUPAL_ADMIN_EMAIL' => '',
      'VORTEX_PROVISION_SANITIZE_DB_EMAIL' => 'user+%uid@localhost',
      'VORTEX_PROVISION_SANITIZE_DB_PASSWORD' => 'testpassword123',
      'VORTEX_PROVISION_SANITIZE_DB_REPLACE_USERNAME_WITH_EMAIL' => '0',
      'VORTEX_PROVISION_SANITIZE_DB_ADDITIONAL_FILE' => '',
      'DRUPAL_PUBLIC_FILES' => 'sites/default/files',
      'DRUPAL_PRIVATE_FILES' => 'sites/default/private',
      'DRUPAL_TEMPORARY_FILES' => '/tmp',
    ]);
  }

  public function testSkipProvisioning(): void {
    $this->envSet('VORTEX_PROVISION_SKIP', '1');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Started site provisioning.', $output);
    $this->assertStringContainsString('Skipped site provisioning as VORTEX_PROVISION_SKIP is set to 1.', $output);
    $this->assertStringContainsString('Finished site provisioning.', $output);
  }

  public function testProvisionFromDbNewSite(): void {
    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);
    $this->mockDbImport();
    $this->mockPostProvisionOps(with_config: TRUE);
    $this->mockSanitization();

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Started site provisioning.', $output);
    $this->assertStringContainsString('Provisioning site from the database dump file.', $output);
    $this->assertStringContainsString('Existing site was not found.', $output);
    $this->assertStringContainsString('Fresh site content will be imported from the database dump file.', $output);
    $this->assertStringContainsString('Imported database from the dump file.', $output);
    $this->assertStringContainsString('Finished site provisioning', $output);
  }

  public function testProvisionFromDbExistingSiteWithOverride(): void {
    $this->envSet('VORTEX_PROVISION_OVERRIDE_DB', '1');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: TRUE, has_config_files: TRUE);
    $this->mockDbImport();
    $this->mockPostProvisionOps(with_config: TRUE);
    $this->mockSanitization();

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Existing site was found.', $output);
    $this->assertStringContainsString('Existing site content will be removed and fresh content will be imported from the database dump file.', $output);
    $this->assertStringContainsString('Imported database from the dump file.', $output);
  }

  public function testProvisionFromDbExistingSiteWithoutOverride(): void {
    $this->envSet('VORTEX_PROVISION_OVERRIDE_DB', '0');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: TRUE, has_config_files: TRUE);
    $this->mockPostProvisionOps(with_config: TRUE);
    // No sanitization because it's skipped for existing DB.

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Existing site was found.', $output);
    $this->assertStringContainsString('Site content will be preserved.', $output);
    $this->assertStringContainsString('Sanitization will be skipped for an existing database.', $output);
    $this->assertStringContainsString('Skipped database sanitization as VORTEX_PROVISION_SANITIZE_DB_SKIP is set to 1.', $output);
  }

  public function testProvisionFromDbWithContainerImageExistingSite(): void {
    $this->envSet('VORTEX_DB_IMAGE', 'myregistry/mydb:latest');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: TRUE, has_config_files: TRUE);
    $this->mockPostProvisionOps(with_config: TRUE);
    $this->mockSanitization();

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Database is baked into the container image.', $output);
    $this->assertStringContainsString('Site content will be preserved.', $output);
    $this->assertStringContainsString('DB dump container image        : myregistry/mydb:latest', $output);
  }

  public function testProvisionFromDbWithContainerImageNoSite(): void {
    $this->envSet('VORTEX_DB_IMAGE', 'myregistry/mydb:latest');

    $this->mockQuit(1);
    $this->expectException(QuitErrorException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Database is baked into the container image.', $output);
    $this->assertStringContainsString('Looks like the database in the container image is corrupted.', $output);
  }

  public function testProvisionFromProfileNewSite(): void {
    $this->envSet('VORTEX_PROVISION_TYPE', 'profile');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);
    $this->mockProfileInstall(has_config_files: TRUE);
    $this->mockPostProvisionOps(with_config: TRUE);
    $this->mockSanitization();

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Provisioning site from the profile.', $output);
    $this->assertStringContainsString('Existing site was not found.', $output);
    $this->assertStringContainsString('Fresh site content will be created from the profile.', $output);
    $this->assertStringContainsString('Installed a site from the profile.', $output);
  }

  public function testProvisionFromProfileExistingSiteWithOverride(): void {
    $this->envSet('VORTEX_PROVISION_TYPE', 'profile');
    $this->envSet('VORTEX_PROVISION_OVERRIDE_DB', '1');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: TRUE, has_config_files: TRUE);
    $this->mockProfileInstall(has_config_files: TRUE);
    $this->mockPostProvisionOps(with_config: TRUE);
    $this->mockSanitization();

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Provisioning site from the profile.', $output);
    $this->assertStringContainsString('Existing site was found.', $output);
    $this->assertStringContainsString('Existing site content will be removed and new content will be created from the profile.', $output);
    $this->assertStringContainsString('Installed a site from the profile.', $output);
  }

  public function testProvisionFromProfileExistingSiteWithoutOverride(): void {
    $this->envSet('VORTEX_PROVISION_TYPE', 'profile');
    $this->envSet('VORTEX_PROVISION_OVERRIDE_DB', '0');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: TRUE, has_config_files: TRUE);
    $this->mockPostProvisionOps(with_config: TRUE);
    // No sanitization because it's skipped for existing DB.

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Provisioning site from the profile.', $output);
    $this->assertStringContainsString('Existing site was found.', $output);
    $this->assertStringContainsString('Site content will be preserved.', $output);
    $this->assertStringContainsString('Sanitization will be skipped for an existing database.', $output);
  }

  public function testPostOperationsSkip(): void {
    $this->envSet('VORTEX_PROVISION_POST_OPERATIONS_SKIP', '1');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);
    $this->mockDbImport();

    // Mock environment output.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\"",
      'output' => 'development',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Skipped running of post-provision operations as VORTEX_PROVISION_POST_OPERATIONS_SKIP is set to 1.', $output);
    $this->assertStringContainsString('Finished site provisioning', $output);
  }

  public function testMaintenanceModeEnabled(): void {
    $this->envSet('VORTEX_PROVISION_USE_MAINTENANCE_MODE', '1');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);
    $this->mockDbImport();
    $this->mockPostProvisionOpsWithMaintenance(with_config: TRUE);
    $this->mockSanitization();
    $this->mockDisableMaintenanceMode();

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Enabling maintenance mode.', $output);
    $this->assertStringContainsString('Enabled maintenance mode.', $output);
    $this->assertStringContainsString('Disabling maintenance mode.', $output);
    $this->assertStringContainsString('Disabled maintenance mode.', $output);
  }

  public function testNoConfigFiles(): void {
    // Remove config files.
    unlink($this->configPath . '/system.site.yml');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: FALSE);
    $this->mockDbImport();
    $this->mockPostProvisionOps(with_config: FALSE);
    $this->mockSanitization();

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Configuration files present    : No', $output);
    $this->assertStringContainsString('Running database updates.', $output);
    $this->assertStringContainsString('Rebuilding cache.', $output);
    $this->assertStringContainsString("Running deployment operations via 'drush deploy:hook'.", $output);
  }

  public function testConfigSplitImport(): void {
    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);
    $this->mockDbImport();
    $this->mockPostProvisionOpsWithConfigSplit();
    $this->mockSanitization();

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Importing config_split configuration.', $output);
    $this->assertStringContainsString('Completed config_split configuration import.', $output);
  }

  public function testMissingDbDumpFile(): void {
    // Remove the db dump file.
    unlink($this->dbDumpPath . '/db.sql');

    $this->mockQuit(1);
    $this->expectException(QuitErrorException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Unable to import database from file.', $output);
    $this->assertStringContainsString('does not exist.', $output);
  }

  public function testSanitizationSkipped(): void {
    $this->envSet('VORTEX_PROVISION_SANITIZE_DB_SKIP', '1');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);
    $this->mockDbImport();
    $this->mockPostProvisionOps(with_config: TRUE);
    // No sanitization mocks needed.

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Skipped database sanitization as VORTEX_PROVISION_SANITIZE_DB_SKIP is set to 1.', $output);
  }

  public function testSanitizationWithUsernameReplacement(): void {
    $this->envSet('VORTEX_PROVISION_SANITIZE_DB_REPLACE_USERNAME_WITH_EMAIL', '1');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);
    $this->mockDbImport();
    $this->mockPostProvisionOps(with_config: TRUE);
    $this->mockSanitizationWithUsernameReplacement();

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Sanitizing database.', $output);
    $this->assertStringContainsString('Updated username with user email.', $output);
  }

  public function testSanitizationWithAdminEmail(): void {
    $this->envSet('DRUPAL_ADMIN_EMAIL', 'admin@example.com');

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);
    $this->mockDbImport();
    $this->mockPostProvisionOps(with_config: TRUE);
    $this->mockSanitizationWithAdminEmail();

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Updated user 1 email.', $output);
  }

  public function testSanitizationWithAdditionalFile(): void {
    // Create additional sanitization file.
    $sanitize_file = self::$tmp . '/scripts/sanitize.sql';
    mkdir(dirname($sanitize_file), 0755, TRUE);
    file_put_contents($sanitize_file, 'UPDATE test SET value = NULL;');
    $this->envSet('VORTEX_PROVISION_SANITIZE_DB_ADDITIONAL_FILE', $sanitize_file);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);
    $this->mockDbImport();
    $this->mockPostProvisionOps(with_config: TRUE);
    $this->mockSanitizationWithAdditionalFile($sanitize_file);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Applied custom sanitization commands from file.', $output);
  }

  public function testCustomProvisionScripts(): void {
    // Create custom scripts directory with a provision script.
    $scripts_dir = self::$tmp . '/scripts/custom';
    mkdir($scripts_dir, 0755, TRUE);
    $script_file = $scripts_dir . '/provision-10-test.sh';
    file_put_contents($script_file, "#!/bin/bash\necho 'Custom script executed'\n");
    chmod($script_file, 0755);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $this->setupDrushMocks(site_installed: FALSE, has_config_files: TRUE);
    $this->mockDbImport();
    $this->mockPostProvisionOps(with_config: TRUE);
    $this->mockSanitization();

    // Mock the custom script execution.
    $this->mockPassthru([
      'cmd' => $script_file,
      'output' => 'Custom script executed',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString("Running custom post-install script '" . $script_file . "'.", $output);
    $this->assertStringContainsString("Completed running of custom post-install script '" . $script_file . "'.", $output);
  }

  public function testMissingConfigDirectory(): void {
    $this->mockQuit(1);
    $this->expectException(QuitErrorException::class);

    // Mock drush version.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y --version',
      'output' => 'Drush Version: 12.0.0',
      'result_code' => 0,
    ]);

    // Mock drupal version.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y status --field=drupal-version 2>/dev/null',
      'output' => '10.2.0',
      'result_code' => 0,
    ]);

    // Mock bootstrap check.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y status --fields=bootstrap 2>/dev/null',
      'output' => '',
      'result_code' => 0,
    ]);

    // Mock config path - return empty.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y php:eval \"print realpath(\\Drupal\\Core\\Site\\Settings::get('config_sync_directory'));\"",
      'output' => '',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Config directory was not found in the Drupal settings.', $output);
  }

  protected function setupDrushMocks(bool $site_installed, bool $has_config_files): void {
    // Mock drush version.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y --version',
      'output' => 'Drush Version: 12.0.0',
      'result_code' => 0,
    ]);

    // Mock drupal version.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y status --field=drupal-version 2>/dev/null',
      'output' => '10.2.0',
      'result_code' => 0,
    ]);

    // Mock bootstrap check.
    $bootstrap_output = $site_installed ? 'Successful' : '';
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y status --fields=bootstrap 2>/dev/null',
      'output' => $bootstrap_output,
      'result_code' => 0,
    ]);

    // Mock config path.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y php:eval \"print realpath(\\Drupal\\Core\\Site\\Settings::get('config_sync_directory'));\"",
      'output' => $this->configPath,
      'result_code' => 0,
    ]);
  }

  protected function mockDbImport(): void {
    // Mock sql:drop.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:drop',
      'output' => 'Tables dropped',
      'result_code' => 0,
    ]);

    // Mock sql:connect.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:connect',
      'output' => 'mysql -u root -h localhost drupal',
      'result_code' => 0,
    ]);

    // Mock the actual import command.
    $this->mockPassthru([
      'cmd' => "mysql -u root -h localhost drupal <'" . $this->dbDumpPath . "/db.sql'",
      'output' => '',
      'result_code' => 0,
    ]);
  }

  protected function mockProfileInstall(bool $has_config_files): void {
    // Mock sql:drop (may fail, that's OK).
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:drop',
      'output' => '',
      'result_code' => 0,
    ]);

    // Build expected site:install command.
    $cmd = "./vendor/bin/drush -y site:install 'standard' --site-name='Test Site' --site-mail='test@example.com' --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL";
    if ($has_config_files) {
      $cmd .= ' --existing-config';
    }

    $this->mockPassthru([
      'cmd' => $cmd,
      'output' => 'Site installed',
      'result_code' => 0,
    ]);
  }

  protected function mockPostProvisionOps(bool $with_config): void {
    // Mock environment output.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\"",
      'output' => 'development',
      'result_code' => 0,
    ]);

    if ($with_config) {
      // Mock config-set for UUID.
      $this->mockPassthru([
        'cmd' => "./vendor/bin/drush -y config-set system.site uuid '12345678-1234-1234-1234-123456789012'",
        'output' => '',
        'result_code' => 0,
      ]);

      // Mock drush deploy.
      $this->mockPassthru([
        'cmd' => './vendor/bin/drush -y deploy',
        'output' => 'Deployment complete',
        'result_code' => 0,
      ]);

      // Mock pm:list check for config_split.
      $this->mockPassthru([
        'cmd' => './vendor/bin/drush -y pm:list --status=enabled',
        'output' => 'some_module',
        'result_code' => 0,
      ]);
    }
    else {
      // Mock updatedb.
      $this->mockPassthru([
        'cmd' => './vendor/bin/drush -y updatedb --no-cache-clear',
        'output' => 'Updates complete',
        'result_code' => 0,
      ]);

      // Mock cache:rebuild.
      $this->mockPassthru([
        'cmd' => './vendor/bin/drush -y cache:rebuild',
        'output' => 'Cache rebuilt',
        'result_code' => 0,
      ]);

      // Mock deploy:hook.
      $this->mockPassthru([
        'cmd' => './vendor/bin/drush -y deploy:hook',
        'output' => 'Hooks executed',
        'result_code' => 0,
      ]);
    }
  }

  protected function mockPostProvisionOpsWithMaintenance(bool $with_config): void {
    // Mock environment output.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\"",
      'output' => 'development',
      'result_code' => 0,
    ]);

    // Mock enable maintenance mode.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y maint:set 1',
      'output' => '',
      'result_code' => 0,
    ]);

    if ($with_config) {
      // Mock config-set for UUID.
      $this->mockPassthru([
        'cmd' => "./vendor/bin/drush -y config-set system.site uuid '12345678-1234-1234-1234-123456789012'",
        'output' => '',
        'result_code' => 0,
      ]);

      // Mock drush deploy.
      $this->mockPassthru([
        'cmd' => './vendor/bin/drush -y deploy',
        'output' => 'Deployment complete',
        'result_code' => 0,
      ]);

      // Mock pm:list check for config_split.
      $this->mockPassthru([
        'cmd' => './vendor/bin/drush -y pm:list --status=enabled',
        'output' => 'some_module',
        'result_code' => 0,
      ]);
    }
    // Note: Sanitization mocks should be called here by the test, then
    // the disable maintenance mode mock should be added after.
  }

  protected function mockDisableMaintenanceMode(): void {
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y maint:set 0',
      'output' => '',
      'result_code' => 0,
    ]);
  }

  protected function mockPostProvisionOpsWithConfigSplit(): void {
    // Mock environment output.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\"",
      'output' => 'development',
      'result_code' => 0,
    ]);

    // Mock config-set for UUID.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y config-set system.site uuid '12345678-1234-1234-1234-123456789012'",
      'output' => '',
      'result_code' => 0,
    ]);

    // Mock drush deploy.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y deploy',
      'output' => 'Deployment complete',
      'result_code' => 0,
    ]);

    // Mock pm:list check for config_split - returns config_split enabled.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y pm:list --status=enabled',
      'output' => 'config_split',
      'result_code' => 0,
    ]);

    // Mock config:import.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y config:import',
      'output' => 'Config imported',
      'result_code' => 0,
    ]);
  }

  protected function mockSanitization(): void {
    // Mock sql:sanitize.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='testpassword123' --sanitize-email='user+%uid@localhost'",
      'output' => 'Sanitized',
      'result_code' => 0,
    ]);

    // Mock reset user 0.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = '', name = '' WHERE uid = '0';\"",
      'output' => '',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET name = '' WHERE uid = '0';\"",
      'output' => '',
      'result_code' => 0,
    ]);
  }

  protected function mockSanitizationWithUsernameReplacement(): void {
    // Mock sql:sanitize.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='testpassword123' --sanitize-email='user+%uid@localhost'",
      'output' => 'Sanitized',
      'result_code' => 0,
    ]);

    // Mock username replacement.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET users_field_data.name=users_field_data.mail WHERE uid <> '0';\"",
      'output' => '',
      'result_code' => 0,
    ]);

    // Mock reset user 0.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = '', name = '' WHERE uid = '0';\"",
      'output' => '',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET name = '' WHERE uid = '0';\"",
      'output' => '',
      'result_code' => 0,
    ]);
  }

  protected function mockSanitizationWithAdminEmail(): void {
    // Mock sql:sanitize.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='testpassword123' --sanitize-email='user+%uid@localhost'",
      'output' => 'Sanitized',
      'result_code' => 0,
    ]);

    // Mock reset user 0.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = '', name = '' WHERE uid = '0';\"",
      'output' => '',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET name = '' WHERE uid = '0';\"",
      'output' => '',
      'result_code' => 0,
    ]);

    // Mock admin email update.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = 'admin@example.com' WHERE uid = '1';\"",
      'output' => '',
      'result_code' => 0,
    ]);
  }

  protected function mockSanitizationWithAdditionalFile(string $file): void {
    // Mock sql:sanitize.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:sanitize --sanitize-password='testpassword123' --sanitize-email='user+%uid@localhost'",
      'output' => 'Sanitized',
      'result_code' => 0,
    ]);

    // Mock additional file import.
    // Note: The file path is converted from ./ to ../ for drush.
    $relative_file = str_replace('./', '../', $file);
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query --file='" . $relative_file . "'",
      'output' => '',
      'result_code' => 0,
    ]);

    // Mock reset user 0.
    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET mail = '', name = '' WHERE uid = '0';\"",
      'output' => '',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => "./vendor/bin/drush -y sql:query \"UPDATE \\`users_field_data\\` SET name = '' WHERE uid = '0';\"",
      'output' => '',
      'result_code' => 0,
    ]);
  }

}
