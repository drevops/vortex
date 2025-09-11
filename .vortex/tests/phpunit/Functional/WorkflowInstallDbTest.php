<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use DrevOps\Vortex\Tests\Traits\Steps\StepAhoyTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepDatabaseTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepEnvironmentTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepFrontendTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepServicesTrait;
use DrevOps\Vortex\Tests\Traits\Steps\StepTestingTrait;

/**
 * Tests DB-driven workflow.
 */
class WorkflowInstallDbTest extends FunctionalTestCase {

  use StepAhoyTrait;
  use StepDatabaseTrait;
  use StepEnvironmentTrait;
  use StepFrontendTrait;
  use StepServicesTrait;
  use StepTestingTrait;

  protected function setUp(): void {
    parent::setUp();

    $this->stepDownloadDb();
  }

  /**
   * Test complete DB-driven workflow.
   */
  public function testDbDrivenWorkflow(): void {
    $this->stepBuild();

    $this->stepAhoyLogin();

    // State-less tests.
    $this->assertFilesTrackedInGit();

    $this->stepAhoyCli();

    $this->stepEnvChanges();

    $this->stepTimezone();

    $this->stepAhoyComposer();

    $this->stepAhoyDrush();

    $this->stepAhoyInfo();

    $this->stepAhoyContainerLogs();

    // State-full tests.
    $this->stepAhoyImportDb();

    $this->stepAhoyExportDb();

    $this->stepAhoyExportDb('mydb.sql');

    $this->stepAhoyImportDb('.data/mydb.sql');

    $this->stepAhoyProvision();

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
