<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Runner;

use Symfony\Component\Process\ExecutableFinder;

/**
 * Provides ExecutableFinder dependency injection.
 */
trait ExecutableFinderAwareTrait {

  /**
   * The executable finder.
   */
  protected ?ExecutableFinder $executableFinder = NULL;

  /**
   * Get the executable finder.
   *
   * Factory method that returns existing finder or creates new one.
   *
   * @return \Symfony\Component\Process\ExecutableFinder
   *   The executable finder instance.
   */
  public function getExecutableFinder(): ExecutableFinder {
    if ($this->executableFinder === NULL) {
      $this->executableFinder = new ExecutableFinder();
    }
    return $this->executableFinder;
  }

  /**
   * Set the executable finder.
   *
   * Allows dependency injection for testing.
   *
   * @param \Symfony\Component\Process\ExecutableFinder $finder
   *   The executable finder instance.
   */
  public function setExecutableFinder(ExecutableFinder $finder): void {
    $this->executableFinder = $finder;
  }

}
