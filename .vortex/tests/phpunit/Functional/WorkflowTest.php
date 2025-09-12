<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;

/**
 * Tests user workflows.
 */
class WorkflowTest extends FunctionalTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->stepDownloadDb();
  }

  /**
   * Smoke test to ensure that the system under test (SUT) is set up correctly.
   */
  public function testSmoke(): void {
    $this->assertDirectoryExists(static::$sut, 'SUT directory exists');
    $this->assertEquals(static::$sut, File::cwd(), 'SUT is the current working directory');
  }

  public function testIdempotence(): void {
    $this->stepBuild();
    $this->assertFilesTrackedInGit();

    $this->stepTestBdd();

    $this->logSubstep('Re-build project to check that the results are identical.');
    $this->stepBuild();
    $this->assertFilesTrackedInGit(skip_commit: TRUE);

    $this->logSubstep('Run BDD tests again on re-built project');
    $this->stepTestBdd();
  }

  /**
   * Test Package token handling during build.
   *
   * Make sure to run with TEST_PACKAGE_TOKEN=working_test_token or this test
   * will fail.
   */
  public function testPackageToken(): void {
    $package_token = getenv('TEST_PACKAGE_TOKEN');
    $this->assertNotEmpty($package_token, 'TEST_PACKAGE_TOKEN environment variable must be set');

    $this->logSubstep('Adding private package to test GitHub token');
    if (file_exists('composer.lock')) {
      unlink('composer.lock');
    }
    $this->cmd('composer config repositories.test-private-package vcs git@github.com:drevops/test-private-package.git');
    $this->cmd('composer require --no-update drevops/test-private-package:^1');

    $this->logSubstep('Build without PACKAGE_TOKEN - should fail');
    $this->stepBuildFailure(env: ['PACKAGE_TOKEN' => '']);

    $this->logSubstep('Build with PACKAGE_TOKEN - should succeed');
    $this->stepBuild(env: ['PACKAGE_TOKEN' => $package_token]);
  }

  /**
   * Test Docker compose workflow without using Ahoy.
   */
  public function testDockerComposeNoAhoy(): void {
    $this->logSubstep('Reset environment');
    $this->cmd('ahoy reset', inp: ['y'], tio: 5 * 60);

    $this->logSubstep('Building stack with docker compose');
    $this->cmd('docker compose build --no-cache', tio: 15 * 60);
    $this->cmd('docker compose up -d --force-recreate', tio: 15 * 60);

    $this->syncToHost();

    $this->logSubstep('Installing dependencies with composer');
    $this->cmd('docker compose exec -T cli composer install --prefer-dist', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli bash -lc "yarn --cwd=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} install --frozen-lockfile"', tio: 10 * 60);

    $this->logSubstep('Provisioning with direct script execution');

    if (!$this->volumesMounted() && file_exists('.data/db.sql')) {
      $this->logNote('Copying database file to container');
      $this->cmd('docker compose exec -T cli mkdir -p .data');
      $this->cmd('docker compose cp -L .data/db.sql cli:/app/.data/db.sql');
      $this->logNote('Building front-end assets in container');
      $this->cmd('docker compose exec -T cli bash -c "cd \${WEBROOT}/themes/custom/\${DRUPAL_THEME} && yarn run build"', tio: 10 * 60);
    }

    $this->cmd('docker compose exec -T cli ./scripts/vortex/provision.sh', tio: 10 * 60);

    $this->syncToHost();

    $this->assertFilesTrackedInGit();

    $this->stepTestBdd();
  }

}
