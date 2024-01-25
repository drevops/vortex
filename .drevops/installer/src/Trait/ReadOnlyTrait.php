<?php

namespace DrevOps\Installer\Trait;

trait ReadOnlyTrait {

  protected $readOnly = FALSE;

  public function setReadOnly($value = TRUE): void {
    $this->readOnly = $value;
  }

  public function isReadOnly(): bool {
    return $this->readOnly;
  }

}
