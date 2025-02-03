<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts;

use Laravel\Prompts\FormStep;

class InstallerFormStep extends FormStep {

  /**
   * {@inheritdoc}
   */
  public function run(array $responses, mixed $previousResponse): mixed {
    if (!$this->shouldRun($responses)) {
      return NULL;
    }

    // Pass the step name to the step closure.
    return ($this->step)($responses, $previousResponse, $this->name);
  }

}
