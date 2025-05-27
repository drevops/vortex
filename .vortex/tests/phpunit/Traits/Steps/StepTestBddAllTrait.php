<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

/**
 * Provides BDD all tests step.
 */
trait StepTestBddAllTrait {

  protected function stepTestBddAll(): void {
    $this->logStepStart();

    // BDD all tests implementation can be added here.
    $this->log('BDD all tests step completed');

    $this->logStepFinish();
  }

}
