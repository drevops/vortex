<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use DrevOps\Vortex\Tests\Traits\Subtests\SubtestAhoyTrait;

/**
 * Tests DB-driven workflow.
 */
class AhoyWorkflowTest extends FunctionalTestCase {

  use SubtestAhoyTrait;

  public function testStateless(): void {
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

  public function testStateful(): void {
    $this->subtestAhoyBuild();

    $this->subtestAhoyImportDb();

    $this->subtestAhoyExportDb();

    $this->subtestAhoyExportDb('mydb.sql');

    $this->subtestAhoyImportDb('.data/mydb.sql');

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

  public function testIdempotence(): void {
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

}
