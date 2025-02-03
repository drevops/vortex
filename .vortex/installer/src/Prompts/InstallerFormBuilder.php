<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts;

use Closure;
use Laravel\Prompts\FormBuilder;

/**
 * Overrides the FormBuilder to use InstallerFormStep.
 */
class InstallerFormBuilder extends FormBuilder {

  /**
   * {@inheritdoc}
   */
  public function add(Closure $step, ?string $name = NULL, bool $ignoreWhenReverting = FALSE): self {
    $this->steps[] = new InstallerFormStep($step, TRUE, $name, $ignoreWhenReverting);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addIf(Closure|bool $condition, Closure $step, ?string $name = NULL, bool $ignoreWhenReverting = FALSE): self {
    $this->steps[] = new InstallerFormStep($step, $condition, $name, $ignoreWhenReverting);

    return $this;
  }

}
