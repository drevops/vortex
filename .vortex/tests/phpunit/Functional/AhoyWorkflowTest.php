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

    $this->subtestAhoyTestKernel();

    $this->subtestAhoyTestFunctional();

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
  public function testAhoyWorkflowDiSi(): void {
    static::$sutInstallerEnv = [
      'VORTEX_INSTALLER_PROMPT_DATABASE_DOWNLOAD_SOURCE' => 'container_registry',
      'VORTEX_INSTALLER_PROMPT_DATABASE_IMAGE' => self::VORTEX_DB_IMAGE_TEST,
    ];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    $this->logSubstep('Verify environment configuration');
    $this->assertFileContainsString('.env', 'VORTEX_DB_DOWNLOAD_SOURCE=container_registry', '.env should contain container registry source');
    $this->assertFileContainsString('.env', 'VORTEX_DB_IMAGE=' . self::VORTEX_DB_IMAGE_TEST, '.env should contain correct database image');
    // Assert that demo config was removed as a part of the installation.
    $this->assertFileNotContainsString('.env', 'VORTEX_DB_IMAGE=drevops/vortex-dev-mariadb-drupal-data-demo-11.x:latest', '.env should not contain demo database image');
    $this->assertFileNotContainsString('.env', 'VORTEX_DB_DOWNLOAD_URL=', '.env should not contain database download URL');

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
    static::$sutInstallerEnv = [
      'VORTEX_INSTALLER_PROMPT_STARTER' => 'install_profile_core',
      'VORTEX_INSTALLER_PROMPT_PROVISION_TYPE' => 'profile',
    ];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    $this->logSubstep('Verify environment configuration');
    $this->assertFileContainsString('.env', 'VORTEX_PROVISION_TYPE=profile', '.env should contain profile provision type');
    $this->assertFileContainsString('.env', 'DRUPAL_PROFILE=standard');

    $this->fileAddVar('.env', 'VORTEX_INSTALLER_IS_DEMO_DB_DOWNLOAD_SKIP', 1);

    $this->subtestAhoyBuild();

    $this->subtestAhoyInfo();

    $this->assertWebpageNotContains('/', 'demo', 'Homepage should not show any demo content ');

    // Cannot run all the tests as DB was refreshed and the provisioning
    // did not run (the post-provisioning hooks did not enable the modules).
    $this->subtestAhoyTestBddFast(tags: 'smoke');
  }

  #[Group('p4')]
  public function testAhoyWorkflowProfileDrupalCms(): void {
    static::$sutInstallerEnv = [
      'VORTEX_INSTALLER_PROMPT_STARTER' => 'install_profile_drupalcms',
      'VORTEX_INSTALLER_PROMPT_PROVISION_TYPE' => 'profile',
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
    $this->cmd('ahoy update-vortex ' . static::$repo . '@' . $latest_installer_commit1, txt: 'Update Vortex to a specific version', env: [
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

}
