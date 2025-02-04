<?php

declare(strict_types=1);

namespace DrevOps\Installer;

use Closure;

class Processor {

  protected array $processors = [];

  public function add(string $name, Closure $processor): self {
    $this->processors[$name] = $processor;
    return $this;
  }

  public function run(string $name, mixed $value): mixed {
    if (isset($this->processors[$name])) {
      return $this->processors[$name]($value);
    }
    return $value;
  }

}
