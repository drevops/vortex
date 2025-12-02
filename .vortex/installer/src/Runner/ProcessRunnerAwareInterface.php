<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Runner;

/**
 * Interface for classes that use ProcessRunner.
 */
interface ProcessRunnerAwareInterface {

  /**
   * Get the process runner.
   *
   * @return \DrevOps\VortexInstaller\Runner\ProcessRunner
   *   The process runner instance.
   */
  public function getProcessRunner(): ProcessRunner;

  /**
   * Set the process runner.
   *
   * @param \DrevOps\VortexInstaller\Runner\ProcessRunner $runner
   *   The process runner instance.
   */
  public function setProcessRunner(ProcessRunner $runner): void;

}
