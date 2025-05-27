<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;
use AlexSkrypnyk\File\Tests\Traits\DirectoryAssertionsTrait;
use DrevOps\Vortex\Tests\Traits\LoggerTrait;

/**
 * Provides BDD testing step.
 */
trait StepTestBddTrait {

  use LoggerTrait;
  use DirectoryAssertionsTrait;

  protected function stepTestBdd(): void {
    $this->logStepStart();

    $this->logSubstep('Running all BDD tests');
    $process = $this->processRun('ahoy', ['test-bdd']);

    if (!$process->isSuccessful()) {
      $this->logSubstep('Re-running all BDD tests after random failure');
      $this->processRun('ahoy', ['test-bdd']);
      $this->assertProcessSuccessful();
    }

    $this->syncToHost();

    $this->logSubstep('Checking that BDD tests have created screenshots and test results');
    $this->assertDirectoryContainsString('html', '.logs/screenshots', message: 'Screenshots directory should not be empty after BDD tests');
    $this->assertFileExists('.logs/test_results/behat/default.xml', 'Behat test results XML file should exist');

    $this->logSubstep('Cleaning up after the test');
    File::remove('.logs/screenshots');
    File::remove('.logs/test_results/behat');
    $this->processRunInContainer('rm', ['-rf', '/app/.logs/screenshots/*']);
    $this->processRunInContainer('rm', ['-rf', '/app/.logs/test_results/*']);

    $this->logStepFinish();
  }

}
