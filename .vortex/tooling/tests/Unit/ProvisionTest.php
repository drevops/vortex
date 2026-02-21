<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for provision script.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[RunTestsInSeparateProcesses]
#[Group('provision')]
class ProvisionTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';

    $this->envSetMultiple([
      'VORTEX_PROVISION_SKIP' => '',
      'VORTEX_PROVISION_TYPE' => 'database',
      'VORTEX_PROVISION_FALLBACK_TO_PROFILE' => '0',
      'VORTEX_PROVISION_OVERRIDE_DB' => '0',
      'VORTEX_PROVISION_SANITIZE_DB_SKIP' => '1',
      'VORTEX_PROVISION_USE_MAINTENANCE_MODE' => '0',
      'VORTEX_PROVISION_POST_OPERATIONS_SKIP' => '1',
      'VORTEX_PROVISION_VERIFY_CONFIG_UNCHANGED_AFTER_UPDATE' => '0',
      'VORTEX_PROVISION_DB' => '',
      'VORTEX_PROVISION_SCRIPTS_DIR' => '/tmp/nonexistent-custom-scripts',
      'WEBROOT' => 'web',
      'DRUPAL_SITE_NAME' => 'Test Site',
      'DRUPAL_SITE_EMAIL' => 'test@example.com',
      'DRUPAL_PROFILE' => 'standard',
      'VORTEX_PROVISION_DB_DIR' => '',
      'VORTEX_DB_DIR' => self::$tmp,
      'VORTEX_PROVISION_DB_FILE' => '',
      'VORTEX_DB_FILE' => 'db.sql',
      'VORTEX_PROVISION_DB_IMAGE' => '',
      'VORTEX_DB_IMAGE' => '',
      'DRUPAL_ADMIN_EMAIL' => '',
    ]);
  }

  public function testSkipProvision(): void {
    $this->envSet('VORTEX_PROVISION_SKIP', '1');

    $this->runScriptEarlyPass('src/provision', 'Skipped site provisioning as VORTEX_PROVISION_SKIP is set to 1.');
  }

  public function testDatabaseProvisionSiteInstalledNoOverride(): void {
    $this->createDbDumpFile();

    $this->mockDrushStartupSequence(TRUE);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Started site provisioning.', $output);
    $this->assertStringContainsString('Existing site was found.', $output);
    $this->assertStringContainsString('Site content will be preserved.', $output);
    $this->assertStringContainsString('Sanitization will be skipped for an existing database.', $output);
  }

  public function testDatabaseProvisionSiteInstalledWithOverride(): void {
    $this->createDbDumpFile();

    $this->envSet('VORTEX_PROVISION_OVERRIDE_DB', '1');

    $this->mockDrushStartupSequence(TRUE);

    // Drush sql:drop.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('sql:drop'),
      'result_code' => 0,
    ]);

    // Drush sql:connect.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('sql:connect'),
      'output' => 'mysql -u root -p test_db',
      'result_code' => 0,
    ]);

    // SQL import via piped command.
    $this->mockPassthru([
      'cmd' => 'mysql -u root -p test_db <' . escapeshellarg(self::$tmp . '/db.sql'),
      'result_code' => 0,
    ]);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Existing site content will be removed and fresh content will be imported', $output);
    $this->assertStringContainsString('Imported database from the dump file.', $output);
  }

  public function testDatabaseProvisionNoSiteFreshImport(): void {
    $this->createDbDumpFile();

    $this->mockDrushStartupSequence(FALSE);

    // Drush sql:drop.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('sql:drop'),
      'result_code' => 0,
    ]);

    // Drush sql:connect.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('sql:connect'),
      'output' => 'mysql -u root -p test_db',
      'result_code' => 0,
    ]);

    // SQL import.
    $this->mockPassthru([
      'cmd' => 'mysql -u root -p test_db <' . escapeshellarg(self::$tmp . '/db.sql'),
      'result_code' => 0,
    ]);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Existing site was not found.', $output);
    $this->assertStringContainsString('Fresh site content will be imported from the database dump file.', $output);
    $this->assertStringContainsString('Imported database from the dump file.', $output);
  }

  public function testDatabaseProvisionNoSiteNoDumpFileFails(): void {
    // Do not create dump file.
    $this->mockDrushStartupSequence(FALSE);

    // Drush sql:drop (called inside provision_from_db before the file check is
    // not reached because the file check happens first).
    // Actually, file check is the first thing in provision_from_db.
    $this->runScriptError('src/provision', 'Unable to import database from file.');
  }

  public function testDatabaseProvisionNoSiteNoDumpFallbackToProfile(): void {
    // Do not create dump file.
    $this->envSet('VORTEX_PROVISION_FALLBACK_TO_PROFILE', '1');

    $this->mockDrushStartupSequence(FALSE);

    // Drush sql:drop (from provision_from_profile).
    $this->mockPassthru([
      'cmd' => $this->drushCmd('sql:drop'),
      'result_code' => 0,
    ]);

    // Drush site:install.
    $this->mockPassthru([
      'cmd' => $this->drushCmd("site:install 'standard' --site-name='Test Site' --site-mail='test@example.com' --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL"),
      'result_code' => 0,
    ]);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Database dump file is not available. Falling back to profile installation.', $output);
    $this->assertStringContainsString('Installed a site from the profile.', $output);
  }

  public function testDatabaseProvisionNoSiteDbImageFallbackToProfile(): void {
    $this->envSetMultiple([
      'VORTEX_DB_IMAGE' => 'myorg/db:latest',
      'VORTEX_PROVISION_FALLBACK_TO_PROFILE' => '1',
    ]);

    $this->mockDrushStartupSequence(FALSE);

    // Drush sql:drop (from provision_from_profile).
    $this->mockPassthru([
      'cmd' => $this->drushCmd('sql:drop'),
      'result_code' => 0,
    ]);

    // Drush site:install.
    $this->mockPassthru([
      'cmd' => $this->drushCmd("site:install 'standard' --site-name='Test Site' --site-mail='test@example.com' --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL"),
      'result_code' => 0,
    ]);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Database in the container image is not available. Falling back to profile installation.', $output);
    $this->assertStringContainsString('Installed a site from the profile.', $output);
  }

  public function testDatabaseProvisionNoSiteDbImageNoFallbackFails(): void {
    $this->envSetMultiple([
      'VORTEX_DB_IMAGE' => 'myorg/db:latest',
      'VORTEX_PROVISION_FALLBACK_TO_PROFILE' => '0',
    ]);

    $this->mockDrushStartupSequence(FALSE);

    $this->runScriptError('src/provision', 'Looks like the database in the container image is corrupted.');
  }

  public function testDatabaseProvisionSiteInstalledDbImage(): void {
    $this->createDbDumpFile();

    $this->envSet('VORTEX_DB_IMAGE', 'myorg/db:latest');

    $this->mockDrushStartupSequence(TRUE);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Database is baked into the container image.', $output);
    $this->assertStringContainsString('Site content will be preserved.', $output);
  }

  public function testProfileProvisionNoSite(): void {
    $this->envSet('VORTEX_PROVISION_TYPE', 'profile');

    $this->mockDrushStartupSequence(FALSE);

    // Drush sql:drop.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('sql:drop'),
      'result_code' => 0,
    ]);

    // Drush site:install.
    $this->mockPassthru([
      'cmd' => $this->drushCmd("site:install 'standard' --site-name='Test Site' --site-mail='test@example.com' --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL"),
      'result_code' => 0,
    ]);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Provisioning site from the profile.', $output);
    $this->assertStringContainsString('Existing site was not found.', $output);
    $this->assertStringContainsString('Fresh site content will be created from the profile.', $output);
    $this->assertStringContainsString('Installed a site from the profile.', $output);
  }

  public function testProfileProvisionSiteInstalledNoOverride(): void {
    $this->envSet('VORTEX_PROVISION_TYPE', 'profile');

    $this->mockDrushStartupSequence(TRUE);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Provisioning site from the profile.', $output);
    $this->assertStringContainsString('Site content will be preserved.', $output);
    $this->assertStringContainsString('Sanitization will be skipped for an existing database.', $output);
  }

  public function testProfileProvisionSiteInstalledWithOverride(): void {
    $this->envSetMultiple([
      'VORTEX_PROVISION_TYPE' => 'profile',
      'VORTEX_PROVISION_OVERRIDE_DB' => '1',
    ]);

    $this->mockDrushStartupSequence(TRUE);

    // Drush sql:drop.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('sql:drop'),
      'result_code' => 0,
    ]);

    // Drush site:install.
    $this->mockPassthru([
      'cmd' => $this->drushCmd("site:install 'standard' --site-name='Test Site' --site-mail='test@example.com' --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL"),
      'result_code' => 0,
    ]);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Existing site content will be removed and new content will be created from the profile.', $output);
    $this->assertStringContainsString('Installed a site from the profile.', $output);
  }

  public function testPostOperationsWithMaintenanceMode(): void {
    $this->createDbDumpFile();

    $this->envSetMultiple([
      'VORTEX_PROVISION_POST_OPERATIONS_SKIP' => '0',
      'VORTEX_PROVISION_USE_MAINTENANCE_MODE' => '1',
    ]);

    $this->mockDrushStartupSequence(TRUE);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    // Drush maint:set 1.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('maint:set 1'),
      'result_code' => 0,
    ]);

    // Drush updatedb.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('updatedb --no-cache-clear'),
      'result_code' => 0,
    ]);

    // Drush cache:rebuild.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('cache:rebuild'),
      'result_code' => 0,
    ]);

    // Drush deploy:hook.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('deploy:hook'),
      'result_code' => 0,
    ]);

    // Drush maint:set 0.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('maint:set 0'),
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Enabling maintenance mode.', $output);
    $this->assertStringContainsString('Completed running database updates.', $output);
    $this->assertStringContainsString('Cache was rebuilt.', $output);
    $this->assertStringContainsString('Completed deployment hooks.', $output);
    $this->assertStringContainsString('Disabling maintenance mode.', $output);
  }

  public function testPostOperationsWithConfigImport(): void {
    $this->createDbDumpFile();
    $this->createConfigFiles();

    $this->envSet('VORTEX_PROVISION_POST_OPERATIONS_SKIP', '0');

    $this->mockDrushStartupSequenceWithConfig(TRUE);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    // Drush config-set system.site uuid.
    $this->mockPassthru([
      'cmd' => $this->drushCmd("config-set system.site uuid 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'"),
      'result_code' => 0,
    ]);

    // Drush updatedb.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('updatedb --no-cache-clear'),
      'result_code' => 0,
    ]);

    // Drush config:import.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('config:import'),
      'result_code' => 0,
    ]);

    // Drush pm:list (config_split check).
    $this->mockPassthru([
      'cmd' => $this->drushCmd('pm:list --status=enabled'),
      'output' => 'config_split',
      'result_code' => 0,
    ]);

    // Drush config:import (config_split).
    $this->mockPassthru([
      'cmd' => $this->drushCmd('config:import'),
      'result_code' => 0,
    ]);

    // Drush cache:rebuild.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('cache:rebuild'),
      'result_code' => 0,
    ]);

    // Drush deploy:hook.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('deploy:hook'),
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Updated site UUID from the configuration', $output);
    $this->assertStringContainsString('Completed running database updates.', $output);
    $this->assertStringContainsString('Completed configuration import.', $output);
    $this->assertStringContainsString('Completed config_split configuration import.', $output);
    $this->assertStringContainsString('Cache was rebuilt.', $output);
    $this->assertStringContainsString('Completed deployment hooks.', $output);
  }

  public function testSummaryOutputIncludesNewVariables(): void {
    $this->createDbDumpFile();

    $this->envSetMultiple([
      'VORTEX_PROVISION_FALLBACK_TO_PROFILE' => '1',
      'VORTEX_PROVISION_VERIFY_CONFIG_UNCHANGED_AFTER_UPDATE' => '1',
    ]);

    $this->mockDrushStartupSequence(TRUE);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('Fallback to profile            : Yes', $output);
    $this->assertStringContainsString('Verify config after update     : Yes', $output);
  }

  public function testProvisionDbDirFallback(): void {
    $this->envSetMultiple([
      'VORTEX_PROVISION_DB_DIR' => '',
      'VORTEX_DB_DIR' => self::$tmp . '/custom-db-dir',
    ]);

    mkdir(self::$tmp . '/custom-db-dir', 0755, TRUE);
    file_put_contents(self::$tmp . '/custom-db-dir/db.sql', 'SQL DUMP');

    $this->mockDrushStartupSequence(TRUE);

    // Drush php:eval (environment).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print \\Drupal\\core\\Site\\Settings::get('environment');\""),
      'output' => 'production',
      'result_code' => 0,
    ]);

    $this->mockQuit(0);
    $this->expectException(QuitSuccessException::class);

    $output = $this->runScript('src/provision');

    $this->assertStringContainsString('custom-db-dir/db.sql', $output);
  }

  protected function drushCmd(string $command): string {
    return './vendor/bin/drush -y ' . $command;
  }

  protected function createDbDumpFile(): void {
    file_put_contents(self::$tmp . '/db.sql', 'SQL DUMP CONTENT');
  }

  protected function createConfigFiles(): void {
    $config_dir = self::$tmp . '/config';
    if (!is_dir($config_dir)) {
      mkdir($config_dir, 0755, TRUE);
    }
    file_put_contents($config_dir . '/system.site.yml', "uuid: a1b2c3d4-e5f6-7890-abcd-ef1234567890\nname: Test Site\n");
    file_put_contents($config_dir . '/core.extension.yml', "module:\n  system: 0\n");
  }

  protected function mockDrushStartupSequence(bool $site_installed, bool $has_config = FALSE): void {
    $config_path = $has_config ? self::$tmp . '/config' : self::$tmp;

    // Drush --version.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('--version'),
      'output' => 'Drush Commandline Tool 13.0.0',
      'result_code' => 0,
    ]);

    // Drush status --field=drupal-version.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('status --field=drupal-version 2>/dev/null'),
      'output' => '10.3.0',
      'result_code' => 0,
    ]);

    // Drush status --fields=bootstrap.
    $this->mockPassthru([
      'cmd' => $this->drushCmd('status --fields=bootstrap 2>/dev/null'),
      'output' => $site_installed ? 'Successful' : '',
      'result_code' => 0,
    ]);

    // Drush php:eval (config_sync_directory).
    $this->mockPassthru([
      'cmd' => $this->drushCmd("php:eval \"print realpath(\\Drupal\\Core\\Site\\Settings::get('config_sync_directory'));\""),
      'output' => $config_path,
      'result_code' => 0,
    ]);
  }

  protected function mockDrushStartupSequenceWithConfig(bool $site_installed): void {
    $this->createConfigFiles();
    $this->mockDrushStartupSequence($site_installed, TRUE);
  }

}
