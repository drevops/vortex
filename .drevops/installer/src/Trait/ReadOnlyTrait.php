<?php

namespace DrevOps\Installer\Trait;

/**
 * Read only trait.
 */
trait ReadOnlyTrait {

  /**
   * The read only flag.
   *
   * @var bool
   */
  protected $readOnly = FALSE;

  /**
   * Sets the read only flag.
   *
   * @param bool $value
   *   The value.
   */
  public function setReadOnly($value = TRUE): void {
    $this->readOnly = $value;
  }

  /**
   * Returns the read only flag.
   *
   * @return bool
   *   The value.
   */
  public function isReadOnly(): bool {
    return $this->readOnly;
  }

}
