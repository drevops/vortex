<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

/**
 * Tests user workflows.
 */
class IdempotenceTest extends FunctionalTestCase {

  public function testIdempotence(): void {
    $this->substepDownloadDb();

    $this->logSubstep('Initial build of the project.');
    $this->stepBuild();
    $this->assertFilesTrackedInGit();

    $this->stepAhoyTestBddFast();

    $this->logSubstep('Re-build project to check that the results are identical.');
    $this->stepBuild();
    $this->assertFilesTrackedInGit(skip_commit: TRUE);

    $this->logSubstep('Run BDD tests again on re-built project');
    $this->stepAhoyTestBddFast();
  }

}
