<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Runner;

use Symfony\Component\Process\ExecutableFinder;

/**
 * Interface for classes that use ExecutableFinder.
 */
interface ExecutableFinderAwareInterface {

  /**
   * Get the executable finder.
   *
   * @return \Symfony\Component\Process\ExecutableFinder
   *   The executable finder instance.
   */
  public function getExecutableFinder(): ExecutableFinder;

  /**
   * Set the executable finder.
   *
   * @param \Symfony\Component\Process\ExecutableFinder $finder
   *   The executable finder instance.
   */
  public function setExecutableFinder(ExecutableFinder $finder): void;

}
