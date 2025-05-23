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

    chdir(static::$sut);

    $this->stepPrepareSut();
    $this->stepDownloadDb();
  }

  /**
   * Smoke test to ensure that the system under test (SUT) is set up correctly.
   */
  public function testSmoke(): void {
    $this->assertDirectoryExists(static::$sut, 'SUT directory exists');
    $this->assertEquals(static::$sut, getcwd(), 'SUT is the current working directory');
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

}
