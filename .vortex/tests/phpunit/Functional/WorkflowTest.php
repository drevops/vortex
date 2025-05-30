<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;

/**
 * Tests user workflows.
 */
class WorkflowTest extends FunctionalTestCase {

  protected function setUp(): void {
    static::logSection('TEST START | ' . $this->name(), double_border: TRUE);

    parent::setUp();

    chdir(static::$sut);

    $this->stepPrepareSut();
    $this->stepDownloadDb();
  }

  protected function tearDown(): void {
    parent::tearDown();

    static::logSection('TEST DONE | ' . $this->name(), double_border: TRUE);
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
   * Test GitHub token handling during build.
   *
   * Make sure to run with TEST_GITHUB_TOKEN=working_test_token or this test
   * will fail.
   */
  public function testGitHubToken(): void {
    $github_token = getenv('TEST_GITHUB_TOKEN');
    $this->assertNotEmpty($github_token, 'TEST_GITHUB_TOKEN environment variable must be set');

    $this->logSubstep('Adding private package to test GitHub token');
    if (file_exists('composer.lock')) {
      unlink('composer.lock');
    }
    $this->processRun('composer config repositories.test-private-package vcs git@github.com:drevops/test-private-package.git');
    $this->assertProcessSuccessful();
    $this->processRun('composer require --no-update drevops/test-private-package:^1');
    $this->assertProcessSuccessful();

    $this->logSubstep('Build without GITHUB_TOKEN - should fail');
    $this->stepBuildFailure(env: ['GITHUB_TOKEN' => '']);

    $this->logSubstep('Build with GITHUB_TOKEN - should succeed');
    $this->stepBuild(env: ['GITHUB_TOKEN' => $github_token]);
  }

}
