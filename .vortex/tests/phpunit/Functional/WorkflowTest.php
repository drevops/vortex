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

    $this->processRunInContainer('rm', ['-rf', '/app/.logs/screenshots/*']);
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
    $this->processRun('composer config repositories.test-private-package vcs git@github.com:drevops/test-private-package.git');
    $this->assertProcessSuccessful();
    $this->processRun('composer require --no-update drevops/test-private-package:^1');
    $this->assertProcessSuccessful();

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
    $this->processRun('ahoy reset', inputs: ['y'], timeout: 5 * 60);
    $this->assertProcessSuccessful();

    $this->logSubstep('Building stack with docker compose');
    $this->processRun('docker compose build --no-cache', timeout: 15 * 60);
    $this->assertProcessSuccessful();
    $this->processRun('docker compose up -d --force-recreate', timeout: 15 * 60);
    $this->assertProcessSuccessful();

    $this->syncToHost();

    $this->logSubstep('Installing dependencies with composer');
    $this->processRun('docker compose exec -T cli composer install --prefer-dist', timeout: 10 * 60);
    $this->assertProcessSuccessful();
    $this->processRun('docker compose exec -T cli yarn --cwd=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} install --frozen-lockfile', timeout: 10 * 60);
    $this->assertProcessSuccessful();

    $this->logSubstep('Provisioning with direct script execution');

    if (!$this->volumesMounted() && file_exists('.data/db.sql')) {
      $this->logNote('Copying database file to container');
      $this->processRun('docker compose exec cli mkdir -p .data');
      $this->assertProcessSuccessful();
      $this->processRun('docker compose cp -L .data/db.sql cli:/app/.data/db.sql');
      $this->assertProcessSuccessful();
      $this->logNote('Installing front-end dependencies on host');
      $this->processRun('docker compose exec -T cli bash -c "cd \${WEBROOT}/themes/custom/\${DRUPAL_THEME} && yarn run build"');
      $this->assertProcessSuccessful();
    }

    $this->processRun('docker compose exec -T cli ./scripts/vortex/provision.sh', timeout: 10 * 60);
    $this->assertProcessSuccessful();

    $this->syncToHost();

    $this->assertFilesTrackedInGit();

    $this->stepTestBdd();
  }

}
