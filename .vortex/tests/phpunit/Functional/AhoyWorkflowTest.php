<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use DrevOps\Vortex\Tests\Traits\Subtests\SubtestAhoyTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Ahoy workflows.
 */
class AhoyWorkflowTest extends FunctionalTestCase {

  use SubtestAhoyTrait;

  protected function setUp(): void {
    parent::setUp();

    static::$sutInstallerEnv = [];
    static::$sutInstallerPrompts = [];

    $this->dockerCleanup();
  }

  #[Group('p1')]
  public function testAhoyWorkflowStateless(): void {
    static::$sutInstallerEnv = ['VORTEX_INSTALLER_IS_DEMO' => '1'];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    $this->subtestAhoyBuild();

    $this->subtestAhoyLogin();

    $this->subtestAhoyDoctor();

    $this->assertFilesTrackedInGit();

    $this->subtestAhoyCli();

    $this->subtestAhoyDotEnv();

    $this->subtestAhoyContainerLogs();

    $this->subtestAhoyComposer();

    $this->subtestAhoyDrush();

    $this->subtestAhoyInfo();

    $this->subtestAhoySolr();

    $this->subtestAhoyDebug();

    $this->subtestAhoyFei();

    $this->subtestAhoyFe();

    $this->subtestAhoyLint();

    $this->subtestAhoyLintBe();

    $this->subtestAhoyLintFe();

    $this->subtestAhoyLintTests();

    $this->subtestAhoyReset();

    $this->subtestAhoyResetHard();
  }

  #[Group('p2')]
  public function testAhoyWorkflowStateful(): void {
    static::$sutInstallerEnv = ['VORTEX_INSTALLER_IS_DEMO' => '1'];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    $this->subtestAhoyBuild();

    $this->subtestAhoyImportDb();

    $this->subtestAhoyExportDb();

    $this->subtestAhoyExportDb('mydb.sql');

    $this->subtestAhoyImportDb('.data/mydb.sql');

    $this->downloadDatabase(TRUE);

    $this->subtestAhoyProvision();

    $this->subtestAhoyTest();

    $this->subtestAhoyTestUnit();

    $this->subtestAhoyTestUnitSettingsLocalSkip();

    $this->subtestAhoyTestKernel();

    $this->subtestAhoyTestFunctional();

    $this->subtestAhoyTestFunctionalJavascript();

    $this->subtestAhoyTestJs();

    $this->subtestAhoyTestBdd();

    // Run this test as a last one to make sure that there is no concurrency
    // issues with enabled Redis.
    $this->subtestAhoyRedis();

    $this->subtestAhoyReset();

    $this->subtestAhoyResetHard();
  }

  #[Group('p3')]
  public function testAhoyBuildIdempotence(): void {
    static::$sutInstallerEnv = ['VORTEX_INSTALLER_IS_DEMO' => '1'];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    $this->logSubstep('Initial build of the project.');
    $this->subtestAhoyBuild();
    $this->assertFilesTrackedInGit();

    $this->logSubstep('Run BDD tests on built project');
    $this->subtestAhoyTestBddFast();

    $this->logSubstep('Re-build project to check that the results are identical.');
    $this->subtestAhoyBuild();
    $this->assertFilesTrackedInGit(skip_commit: TRUE);

    $this->logSubstep('Run BDD tests again on re-built project');
    $this->subtestAhoyTestBddFast();
  }

  #[Group('p4')]
  public function testAhoyWorkflowDatabaseFromImageStorageInImage(): void {
    static::$sutInstallerPrompts = [
      'database_download_source' => 'container_registry',
      'database_image' => self::VORTEX_DB_IMAGE_TEST,
    ];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    $this->logSubstep('Verify environment configuration');
    $this->assertFileContainsString('.env', 'VORTEX_DOWNLOAD_DB_SOURCE=container_registry', '.env should contain container registry source');
    $this->assertFileContainsString('.env', 'VORTEX_DB_IMAGE=' . self::VORTEX_DB_IMAGE_TEST, '.env should contain correct database image');
    // Assert that demo config was removed as a part of the installation.
    $this->assertFileNotContainsString('.env', 'VORTEX_DB_IMAGE=drevops/vortex-dev-mariadb-drupal-data-demo-11.x:1.38.0-rc1', '.env should not contain demo database image');
    $this->assertFileNotContainsString('.env', 'VORTEX_DOWNLOAD_DB_URL=', '.env should not contain database download URL');

    // Do not use demo database - testing demo database discovery is
    // another test.
    $this->fileAddVar('.env', 'VORTEX_INSTALLER_IS_DEMO_DB_DOWNLOAD_SKIP', 1);

    // Explicitly specify that we do not want to login into the public registry
    // to use test image.
    $this->fileAddVar('.env', 'VORTEX_CONTAINER_REGISTRY_USER', '');
    $this->fileAddVar('.env', 'VORTEX_CONTAINER_REGISTRY_PASS', '');

    $this->subtestAhoyBuild();

    $this->subtestAhoyInfo(db_image: self::VORTEX_DB_IMAGE_TEST);

    // Assert that the database was not downloaded
    // because VORTEX_INSTALLER_IS_DEMO_DB_DOWNLOAD_SKIP was set.
    $this->assertFileDoesNotExist('.data/db.sql', 'Demo database file should not exist after installer');

    $this->logSubstep('Test database reload functionality');
    $this->assertWebpageContains('/', 'This test page is sourced from the Vortex database container image', 'Homepage should show test content from database image');

    $this->logSubstep('Change homepage content and assert that the change was applied');
    $this->cmd('ahoy drush config-set system.site page.front /user -y', txt: 'Change homepage to user page');
    $this->assertWebpageNotContains('/', 'This test page is sourced from the Vortex database container image', 'Homepage should not show initial test content after config change');

    $this->logSubstep('Reload database from the container image and assert that the initial content is restored');
    $this->cmd('ahoy reload-db', txt: "`ahoy reload-db` restarts the stack fast", tio: 60);
    // @note Redis caches are not flushed automatically on cache clear as it
    // may be clearing too much.
    // For now, we are manually clearing Redis cache after DB reload. A human
    // operator would make a call to do it manually depending on the hosting,
    // website size, traffic, etc.
    // @see https://www.drupal.org/project/redis/issues/2765895
    $this->cmd('ahoy flush-redis', txt: "`ahoy flush-redis` flushes Redis cache after database reload", tio: 30);
    $this->subtestAhoyInfo(db_image: self::VORTEX_DB_IMAGE_TEST);
    $this->assertWebpageContains('/', 'This test page is sourced from the Vortex database container image', 'Homepage should show initial test content after database reload');

    // The previous step was only testing the image re-loading capability (we
    // used the content in the image to check that the database reload from
    // image worked), but we did not verify that the site is fully functional.
    // We now should run the updates and check the rest of the site.
    $this->cmd('ahoy drush updb -y', txt: 'Run database updates after reload', tio: 60);
    $this->cmd('ahoy drush cr', txt: 'Clear caches after reload', tio: 30);

    // Other stack assertions - these run only for this container
    // image-related test.
    $this->assertFilesTrackedInGit();

    $this->subtestAhoyContainerLogs();

    $this->subtestAhoyLogin();

    $this->subtestAhoyExportDb('mydb.tar', TRUE);

    // Cannot run all the tests as DB was refreshed and the provisioning
    // did not run (the post-provisioning hooks did not enable the modules).
    $this->subtestAhoyTestBddFast(tags: 'smoke');
  }

  #[Group('p1')]
  public function testAhoyWorkflowProfileStandard(): void {
    static::$sutInstallerPrompts = [
      'starter' => 'install_profile_core',
      'provision_type' => 'profile',
    ];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    $this->logSubstep('Verify environment configuration');
    $this->assertFileContainsString('.env', 'VORTEX_PROVISION_TYPE=profile', '.env should contain profile provision type');
    $this->assertFileContainsString('.env', 'DRUPAL_PROFILE=standard');

    $this->fileAddVar('.env', 'VORTEX_INSTALLER_IS_DEMO_DB_DOWNLOAD_SKIP', 1);

    $this->subtestAhoyBuild();

    $this->subtestAhoyInfo();

    $this->assertWebpageNotContains('/', 'demo site', 'Homepage should not show any demo database content');

    // Cannot run all the tests as DB was refreshed and the provisioning
    // did not run (the post-provisioning hooks did not enable the modules).
    $this->subtestAhoyTestBddFast(tags: 'smoke');
  }

  #[Group('p4')]
  public function testAhoyWorkflowProfileDrupalCms(): void {
    static::$sutInstallerPrompts = [
      'starter' => 'install_profile_drupalcms',
      'provision_type' => 'profile',
    ];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    $this->logSubstep('Verify environment configuration');
    $this->assertFileContainsString('.env', 'VORTEX_PROVISION_TYPE=profile', '.env should contain profile provision type');
    $this->assertFileContainsString('.env', 'DRUPAL_PROFILE=../recipes/drupal_cms_starter');

    $this->fileAddVar('.env', 'VORTEX_INSTALLER_IS_DEMO_DB_DOWNLOAD_SKIP', 1);

    $this->subtestAhoyBuild();

    $this->assertFileContainsString('composer.json', '"drupal/cms"');
    $this->assertFileContainsString('composer.json', '"wikimedia/composer-merge-plugin"');
    $this->assertFileContainsString('composer.json', '"vendor/drupal/cms/composer.json"');

    $this->subtestAhoyInfo();

    $this->assertWebpageContains('/', 'This is the home page of your new site.', 'Homepage should show Drupal CMS profile content ');

    // Cannot run all the tests as DB was refreshed and the provisioning
    // did not run (the post-provisioning hooks did not enable the modules).
    $this->subtestAhoyTestBddFast(tags: 'smoke,counter');
  }

  #[Group('p4')]
  public function testAhoyWorkflowMigration(): void {
    static::$sutInstallerEnv = [
      'VORTEX_INSTALLER_IS_DEMO' => '1',
    ];
    static::$sutInstallerPrompts = [
      'migration' => TRUE,
      'migration_download_source' => 'url',
    ];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    // Verify installer produced the migration infrastructure.
    $this->subtestAhoyMigrationFilesPresent();

    // Download migration database before build so it is available when
    // provisioning runs during `ahoy build`.
    $this->subtestAhoyMigrationDownloadDb();

    $this->subtestAhoyBuild();

    $this->downloadDatabase(TRUE);

    $this->subtestAhoyMigrationProvision();

    $this->subtestAhoyMigrationSkip();
  }

  #[Group('p4')]
  public function testAhoyWorkflowMigrationDatabaseFromImage(): void {
    static::$sutInstallerEnv = [
      'VORTEX_INSTALLER_IS_DEMO' => '1',
    ];
    static::$sutInstallerPrompts = [
      'migration' => TRUE,
      'migration_download_source' => 'container_registry',
      'migration_image' => self::VORTEX_DB_IMAGE_TEST,
    ];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    // Verify installer produced the migration infrastructure.
    $this->subtestAhoyMigrationFilesPresent();

    $this->logSubstep('Verify migration database image configuration');
    $this->assertFileContainsString('.env', 'VORTEX_DOWNLOAD_DB2_SOURCE=container_registry', '.env should contain container registry source for migration');
    $this->assertFileContainsString('.env', 'VORTEX_DB2_IMAGE=' . self::VORTEX_DB_IMAGE_TEST, '.env should contain migration database image');

    // Skip migration during build - we are testing the container reload
    // mechanism, not actual migrations. The test image has a standard Drupal
    // database, not the migration source data.
    $this->fileAddVar('.env', 'DRUPAL_MIGRATION_SKIP', '1');

    $this->subtestAhoyBuild();

    $this->subtestAhoyMigrationReloadDb();
  }

  #[Group('p4')]
  public function testAhoyUpdateVortexLatest(): void {
    // For test performance, we only export the current codebase without git
    // history in the setUp(). For this test, though, we need git history to
    // simulate Vortex template repository.
    $this->logSubstep('Prepare Vortex template repository');
    $this->gitInitRepo(static::$repo);
    $this->gitCommitAll(static::$repo, 'Initial Vortex template commit');
    $this->gitAssertClean(static::$repo, 'Git working tree of the Vortex template repository should be clean');

    $this->logSubstep('Add custom files to SUT');
    File::dump('test1.txt', 'test content');
    // File resides in directory that is included in Vortex when initialised.
    File::dump('.docker/test2.txt', 'test docker content');
    $this->gitInitRepo(static::$sut);
    $this->gitCommitAll(static::$sut, 'First commit');

    $this->logSubstep('Run Vortex installer to populate SUT with Vortex files');
    $this->runInstaller();
    $this->assertCommonFilesPresent();
    $this->gitCommitAll(static::$sut, 'Init Vortex');

    // Assert that custom files were preserved.
    $this->assertFileExists('test1.txt', 'Custom file should be preserved after Vortex installation');
    $this->assertFileExists('.docker/test2.txt', 'Custom file in Vortex directory should be preserved');
    $this->gitAssertClean(static::$sut, 'SUT git working tree should be clean after Vortex installation');

    $this->logSubstep('Simulate dependencies installation');
    $this->createInstalledDependenciesStub();
    $this->gitCommitAll(static::$sut, 'Added SUT dependencies');

    $this->logSubstep('Adding new commits to Vortex');

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 1 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/.eslintrc.json', "\n# Update 1 to Vortex in .eslintrc.json");
    $latest_installer_commit1 = $this->gitCommitAll(static::$repo, 'Added update 1 to Vortex');
    $this->logNote(sprintf('Update 1 Vortex version commit hash: %s', $latest_installer_commit1));

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 2 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/.eslintrc.json', "\n# Update 2 to Vortex in .eslintrc.json");
    $latest_installer_commit2 = $this->gitCommitAll(static::$repo, 'Added update 2 to Vortex');
    $this->logNote(sprintf('Update 2 Vortex version commit hash: %s', $latest_installer_commit2));

    $this->logSubstep('Build installer to be used for update');
    // This is required as the update script will remove the installer after
    // the update.
    $installer_bin = $this->buildInstaller();

    $this->logSubstep('Update Vortex from the template repository');
    $this->cmd('ahoy update-vortex', env: [
      // Use environment variable for this test instead of the argument.
      'VORTEX_INSTALLER_TEMPLATE_REPO' => static::$repo,
      // Override installer path to be called from SUT's update script.
      'VORTEX_INSTALLER_URL' => 'file://' . $installer_bin,
      // Do not suppress the installer output so it could be used in assertions.
      'SHELL_VERBOSITY' => FALSE,
    ]);
    $this->assertProcessOutputContains(static::$repo);
    $this->assertProcessOutputNotContains($latest_installer_commit1);
    $this->assertProcessOutputNotContains($latest_installer_commit2);
    $this->assertProcessOutputContains('HEAD');
    $this->gitAssertIsRepository(static::$sut);
    $this->assertCommonFilesPresent(vortex_version: 'develop');

    $this->logSubstep('Assert that committed files were updated');
    $this->assertFileContainsString('docker-compose.yml', '# Update 1 to Vortex in docker-compose.yml', 'docker-compose.yml should contain update 1 changes');
    $this->assertFileContainsString('docker-compose.yml', '# Update 2 to Vortex in docker-compose.yml', 'docker-compose.yml should contain update 2 changes');
    $this->assertFileContainsString('web/themes/custom/star_wars/.eslintrc.json', '# Update 1 to Vortex in .eslintrc.json', 'Theme .eslintrc.json should contain update 1 changes');
    $this->assertFileContainsString('web/themes/custom/star_wars/.eslintrc.json', '# Update 2 to Vortex in .eslintrc.json', 'Theme .eslintrc.json should contain update 2 changes');

    $this->logSubstep('Assert that new changes need to be manually resolved');
    $this->gitAssertNotClean(static::$sut, 'Git working tree should not be clean after Vortex update');

    $this->logSubstep('Assert that installer script was removed');
    $this->assertFileDoesNotExist('installer.php', 'Installer script should be removed after update');
  }

  #[Group('p4')]
  public function testAhoyUpdateVortexRef(): void {
    // For test performance, we only export the current codebase without git
    // history in the setUp(). For this test, though, we need git history to
    // simulate Vortex template repository.
    $this->logSubstep('Prepare Vortex template repository');
    $this->gitInitRepo(static::$repo);
    $this->gitCommitAll(static::$repo, 'Initial Vortex template commit');
    $this->gitAssertClean(static::$repo, 'Git working tree of the Vortex template repository should be clean');

    $this->logSubstep('Add custom files to SUT');
    File::dump('test1.txt', 'test content');
    // File resides in directory that is included in Vortex when initialised.
    File::dump('.docker/test2.txt', 'test docker content');
    $this->gitInitRepo(static::$sut);
    $this->gitCommitAll(static::$sut, 'First commit');

    $this->logSubstep('Run Vortex installer to populate SUT with Vortex files');
    $this->runInstaller();
    $this->assertCommonFilesPresent();
    $this->gitCommitAll(static::$sut, 'Init Vortex');

    // Assert that custom files were preserved.
    $this->assertFileExists('test1.txt', 'Custom file should be preserved after Vortex installation');
    $this->assertFileExists('.docker/test2.txt', 'Custom file in Vortex directory should be preserved');
    $this->gitAssertClean(static::$sut, 'SUT git working tree should be clean after Vortex installation');

    $this->logSubstep('Simulate dependencies installation');
    $this->createInstalledDependenciesStub();
    $this->gitCommitAll(static::$sut, 'Added SUT dependencies');

    $this->logSubstep('Adding new commits to Vortex');

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 1 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/.eslintrc.json', "\n# Update 1 to Vortex in .eslintrc.json");
    $latest_installer_commit1 = $this->gitCommitAll(static::$repo, 'Added update 1 to Vortex');
    $this->logNote(sprintf('Update 1 Vortex version commit hash: %s', $latest_installer_commit1));

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 2 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/.eslintrc.json', "\n# Update 2 to Vortex in .eslintrc.json");
    $latest_installer_commit2 = $this->gitCommitAll(static::$repo, 'Added update 2 to Vortex');
    $this->logNote(sprintf('Update 2 Vortex version commit hash: %s', $latest_installer_commit2));

    $this->logSubstep('Build installer to be used for update');
    // This is required as the update script will remove the installer after
    // the update.
    $installer_bin = $this->buildInstaller();

    $this->logSubstep('Update Vortex from the template repository');
    // Use the argument instead of `VORTEX_INSTALLER_TEMPLATE_REPO` variable.
    $this->cmd('ahoy update-vortex ' . static::$repo . '#' . $latest_installer_commit1, txt: 'Update Vortex to a specific version', env: [
      // Override installer path to be called from SUT's update script.
      'VORTEX_INSTALLER_URL' => 'file://' . $installer_bin,
      // Do not suppress the installer output so it could be used in assertions.
      'SHELL_VERBOSITY' => FALSE,
    ]);
    $this->assertProcessOutputContains(static::$repo);
    $this->assertProcessOutputContains($latest_installer_commit1);
    $this->assertProcessOutputNotContains($latest_installer_commit2);
    $this->gitAssertIsRepository(static::$sut);
    $this->assertCommonFilesPresent(vortex_version: $latest_installer_commit1);

    $this->logSubstep('Assert that committed files were updated');
    $this->assertFileContainsString('docker-compose.yml', '# Update 1 to Vortex in docker-compose.yml', 'docker-compose.yml should contain update 1 changes');
    $this->assertFileNotContainsString('docker-compose.yml', '# Update 2 to Vortex in docker-compose.yml', 'docker-compose.yml should not contain update 2 changes');
    $this->assertFileContainsString('web/themes/custom/star_wars/.eslintrc.json', '# Update 1 to Vortex in .eslintrc.json', 'Theme .eslintrc.json should contain update 1 changes');
    $this->assertFileNotContainsString('web/themes/custom/star_wars/.eslintrc.json', '# Update 2 to Vortex in .eslintrc.json', 'Theme .eslintrc.json should not contain update 2 changes');

    $this->logSubstep('Assert that new changes need to be manually resolved');
    $this->gitAssertNotClean(static::$sut, 'Git working tree should not be clean after Vortex update');

    $this->logSubstep('Assert that installer script was removed');
    $this->assertFileDoesNotExist('installer.php', 'Installer script should be removed after update');
  }

  #[Group('p0')]
  public function testAhoyWorkflowProvisionFallbackToProfile(): void {
    static::$sutInstallerEnv = ['VORTEX_INSTALLER_IS_DEMO' => '1'];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    $this->logSubstep('Build the site with database dump');
    $this->subtestAhoyBuild();

    $this->logSubstep('Export configuration from the provisioned site');
    $this->cmd('ahoy drush cex -y', '* ../config/default', 'Export configuration should complete successfully');
    $this->syncToHost('config');
    $this->assertFilesWildcardExists('config/default/*.yml');

    $this->logSubstep('Remove the database dump file');
    $this->removePathHostAndContainer('.data/db.sql');
    $this->assertFileDoesNotExist('.data/db.sql', 'Database dump file should not exist after removal');

    $this->logSubstep('Drop the database to simulate a fresh environment');
    $this->cmd('ahoy drush sql:drop -y', txt: 'Database should be dropped successfully');

    $this->logSubstep('Provision without fallback should fail');
    $this->fileAddVar('.env', 'VORTEX_PROVISION_FALLBACK_TO_PROFILE', 0);
    $this->syncToContainer(['.env']);
    $this->cmdFail(
      'ahoy provision',
      [
        '* Unable to import database from file',
        '* does not exist',
        '* Site content was not changed',
      ],
      'Provision without fallback should fail when no database dump is available',
    );

    $this->logSubstep('Provision with fallback should succeed');
    $this->fileAddVar('.env', 'VORTEX_PROVISION_FALLBACK_TO_PROFILE', 1);
    $this->syncToContainer(['.env']);
    $this->cmd(
      'ahoy provision',
      [
        '* Database dump file is not available. Falling back to profile installation',
        '* Installed a site from the profile',
        '* Skipped running of post-provision operations',
        '! Importing configuration',
        '! Running deployment hooks',
      ],
      'Provision with fallback should complete successfully',
      tio: 15 * 60,
    );

    $this->logSubstep('Assert that Shield module is enabled');
    $this->cmd('ahoy drush pm:list --status=enabled --type=module --format=list', '* shield', 'Shield module should be enabled after fallback provision');

    // Diagnostic probes to understand why the homepage hits the redirect
    // module after a fallback profile install. Each command's full output is
    // streamed to the test log; failures here are non-fatal so we still reach
    // the original assertions below.
    // @todo Remove once the underlying cause is understood and fixed.
    $this->logSubstep('DEBUG: enabled modules per core.extension');
    $this->cmd('ahoy drush pm:list --status=enabled --type=module --format=list');

    $this->logSubstep('DEBUG: core.extension config dump');
    $this->cmd('ahoy drush config:get core.extension');

    $this->logSubstep('DEBUG: redirect module status (should be disabled after fallback)');
    $this->cmd('ahoy drush pm:list --filter=redirect --format=list');

    $this->logSubstep('DEBUG: all DB tables');
    $this->cmd('ahoy drush sql:query "SHOW TABLES"');

    $this->logSubstep('DEBUG: redirect-related DB tables (should be empty)');
    $this->cmd('ahoy drush sql:query "SHOW TABLES LIKE \'redirect%\'"');

    $this->logSubstep('DEBUG: cache_container row count');
    $this->cmd('ahoy drush sql:query "SELECT COUNT(*) FROM cache_container"');

    $this->logSubstep('DEBUG: PhpStorage layout under web/sites/default/files/php');
    $this->cmd('ahoy cli "ls -la web/sites/default/files/php/ 2>/dev/null || echo NO_PHP_DIR"');
    $this->cmd('ahoy cli "ls -la web/sites/default/files/php/container/ 2>/dev/null || echo NO_CONTAINER_DIR"');
    $this->cmd('ahoy cli "find web/sites/default/files/php -type f 2>/dev/null | head -30 || echo NO_FILES"');

    $this->logSubstep('DEBUG: presence of redirect references in cached container files');
    $this->cmd('ahoy cli "grep -l \"Drupal..redirect\" web/sites/default/files/php/container/*.php 2>/dev/null || echo NO_REDIRECT_IN_CONTAINER_CACHE"');
    $this->cmd('ahoy cli "grep -c \"redirect.request_subscriber\\|RedirectRequestSubscriber\" web/sites/default/files/php/container/*.php 2>/dev/null || echo NO_REDIRECT_SUBSCRIBER_IN_CACHE"');

    $this->logSubstep('DEBUG: hash_salt and deployment_identifier');
    $this->cmd('ahoy drush php:eval "print \"hash_salt=\" . substr(\Drupal\Core\Site\Settings::get(\"hash_salt\"), 0, 12) . PHP_EOL . \"deployment_identifier=\" . (\Drupal\Core\Site\Settings::get(\"deployment_identifier\") ?? \"(null)\") . PHP_EOL;"');

    $this->logSubstep('DEBUG: runtime active modules per Drupal kernel');
    $this->cmd('ahoy drush php:eval "foreach (array_keys(\Drupal::moduleHandler()->getModuleList()) as \$m) { print \$m . PHP_EOL; }"');

    $this->logSubstep('DEBUG: registered path_processor_inbound services');
    $this->cmd('ahoy drush php:eval "foreach (\Drupal::getContainer()->getServiceIds() as \$id) { if (str_contains(\$id, \"path_processor\") || str_contains(\$id, \"redirect\")) { print \$id . PHP_EOL; } }"');

    $this->logSubstep('DEBUG: last 30 watchdog entries');
    $this->cmd('ahoy drush watchdog:show --count=30 || true');

    $this->logSubstep('DEBUG: head of homepage response');
    $this->cmd('ahoy cli "curl -sS -o - -w \"\\nHTTP_STATUS=%{http_code}\\n\" http://nginx:8080/ | head -c 4000"');

    $this->logSubstep('Assert that homepage does not contain database dump content');
    $this->assertWebpageNotContains('/', 'This demo page is sourced from the Vortex database dump file', 'Homepage should not show database dump content after fallback provision');

    $this->logSubstep('Assert that homepage is accessible');
    $this->assertWebpageContains('/', '<html', 'Homepage should be a valid HTML page');
  }

}
