<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;
use DrevOps\Vortex\Tests\Traits\LoggerTrait;

/**
 * Provides BDD testing step.
 */
trait StepTestBddTrait {

  use LoggerTrait;

  protected function stepTestBdd(): void {
    $this->logStepStart();

    $this->stepWarmCaches();

    $this->logSubstep('Running all BDD tests');
    $process = $this->processRun('ahoy test-bdd');

    if (!$process->isSuccessful()) {
      $this->logSubstep('Re-running all BDD tests after random failure');
      $this->cmd('ahoy test-bdd');
    }

    $this->syncToHost();

    $this->logSubstep('Checking that BDD tests have created screenshots and test results');
    $this->assertDirectoryContainsString('.logs/screenshots', 'html', message: 'Screenshots directory should not be empty after BDD tests');
    $this->assertFileExists('.logs/test_results/behat/default.xml', 'Behat test results XML file should exist');

    $this->logSubstep('Cleaning up after the test');
    File::remove(['.logs/screenshots', '.logs/test_results/behat']);
    $this->cmd('ahoy cli rm -rf /app/.logs/screenshots/*');
    $this->cmd('ahoy cli rm -rf /app/.logs/test_results/*');

    $this->logStepFinish();
  }

  protected function stepWarmCaches(): void {
    $this->logSubstep('Warming up caches');
    $this->cmd('ahoy drush cr');
    $this->cmd('ahoy cli curl -- -sSL -o /dev/null -w "%{http_code}" http://nginx:8080 | grep -q 200');
  }

}
