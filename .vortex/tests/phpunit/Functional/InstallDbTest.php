<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use DrevOps\Vortex\Tests\Traits\Steps\SubtestAhoyTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepEnvironmentTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepFrontendTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepServicesTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepTestTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepLintTrait;

/**
 * Tests DB-driven workflow.
 */
class InstallDbTest extends FunctionalTestCase {

  use SubtestAhoyTrait;
  use StepEnvironmentTrait;
  use StepFrontendTrait;
  use StepLintTrait;
  use StepServicesTrait;
  use StepTestTrait;

  protected function setUp(): void {
    parent::setUp();

    $this->substepDownloadDb();
  }

  /**
   * Test complete DB-driven workflow.
   */
  public function testDbDrivenWorkflow(): void {
    $this->stepBuild();

    $this->subtestAhoyLogin();

    // State-less tests.
    $this->assertFilesTrackedInGit();

    $this->subtestAhoyCli();

    $this->stepEnvChanges();

    $this->stepTimezone();

    $this->subtestAhoyComposer();

    $this->subtestAhoyDrush();

    $this->subtestAhoyInfo();

    $this->subtestAhoyContainerLogs();

    // State-full tests.
    $this->subtestAhoyImportDb();

    $this->subtestAhoyExportDb();

    $this->subtestAhoyExportDb('mydb.sql');

    $this->subtestAhoyImportDb('.data/mydb.sql');

    $this->subtestAhoyProvision();

    $this->stepAhoyLint();

    $this->stepAhoyTest();

    $this->stepAhoyFei();

    $this->stepAhoyFe();

    $this->stepAhoyDebug();

    $this->stepSolr();

    // Run this test as a last one to make sure that there is no concurrency
    // issues with enabled Valkey.
    $this->stepRedis();

    $this->stepAhoyReset();

    $this->stepAhoyResetHard();
  }

}
