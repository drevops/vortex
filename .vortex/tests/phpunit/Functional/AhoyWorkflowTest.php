<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use DrevOps\Vortex\Tests\Traits\Subtests\SubtestAhoyTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests DB-driven workflow.
 */
class AhoyWorkflowTest extends FunctionalTestCase {

  use SubtestAhoyTrait;

  protected function setUp(): void {
    parent::setUp();

    // A bit hacky way to set a different installer theme for NoFe tests here
    // rather than within the test as the installer runs in the parent::setUp().
    if (str_contains($this->name(), 'DiSi')) {
      static::$sutInstallerEnv['VORTEX_INSTALLER_PROMPT_DATABASE_DOWNLOAD_SOURCE'] = 'container_registry';
      static::$sutInstallerEnv['VORTEX_INSTALLER_PROMPT_DATABASE_IMAGE'] = 'drevops/vortex-dev-mariadb-drupal-data-test-11.x:latest';
    }
    elseif (str_contains($this->name(), 'ProfileStandard')) {
      static::$sutInstallerEnv['VORTEX_INSTALLER_PROMPT_STARTER'] = 'install_profile_core';
      static::$sutInstallerEnv['VORTEX_INSTALLER_PROMPT_PROVISION_TYPE'] = 'profile';
    }
    elseif (str_contains($this->name(), 'ProfileDrupalCms')) {
      static::$sutInstallerEnv['VORTEX_INSTALLER_PROMPT_STARTER'] = 'install_profile_drupalcms';
      static::$sutInstallerEnv['VORTEX_INSTALLER_PROMPT_PROVISION_TYPE'] = 'profile';
    }
    else {
      unset(static::$sutInstallerEnv['VORTEX_INSTALLER_PROMPT_DATABASE_DOWNLOAD_SOURCE']);
      unset(static::$sutInstallerEnv['VORTEX_INSTALLER_PROMPT_DATABASE_IMAGE']);
      unset(static::$sutInstallerEnv['VORTEX_INSTALLER_PROMPT_STARTER']);
      unset(static::$sutInstallerEnv['VORTEX_INSTALLER_PROMPT_PROVISION_TYPE']);

      static::$sutInstallerEnv['VORTEX_INSTALLER_IS_DEMO'] = '1';
    }

    $this->prepareSut();

    $this->dockerCleanup();

    $this->adjustAhoyForUnmountedVolumes();
  }

  #[Group('p1')]
  public function testAhoyWorkflowStateless(): void {
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

  #[Group('p1')]
  public function testAhoyWorkflowStateful(): void {
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

  #[Group('p1')]
  public function testAhoyBuildIdempotence(): void {
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
    $this->logStepStart();

    $db_image = 'drevops/vortex-dev-mariadb-drupal-data-test-11.x:latest';

    $this->logSubstep('Verify environment configuration');
    $this->assertFileContainsString('.env', 'VORTEX_DB_DOWNLOAD_SOURCE=container_registry', '.env should contain container registry source');
    $this->assertFileContainsString('.env', 'VORTEX_DB_IMAGE=' . $db_image, '.env should contain correct database image');
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

    $this->subtestAhoyInfo(db_image: $db_image);

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
    $this->subtestAhoyInfo(db_image: $db_image);
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

    $this->logStepFinish();
  }

  #[Group('p2')]
  public function testAhoyWorkflowProfileStandard(): void {
    $this->logStepStart();

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

    $this->logStepFinish();
  }

  #[Group('p3')]
  public function testAhoyWorkflowProfileDrupalCms(): void {
    $this->logStepStart();

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

    $this->logStepFinish();
  }

}
