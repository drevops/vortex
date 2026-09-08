<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Runner;

/**
 * Provides ProcessRunner dependency injection.
 */
trait ProcessRunnerAwareTrait {

  /**
   * The process runner.
   */
  protected ?ProcessRunner $processRunner = NULL;

  /**
   * Get the process runner.
   *
   * @return \DrevOps\VortexInstaller\Runner\ProcessRunner
   *   The process runner instance.
   */
  public function getProcessRunner(): ProcessRunner {
    return $this->processRunner ??= new ProcessRunner();
  }

  /**
   * Set the process runner.
   *
   * @param \DrevOps\VortexInstaller\Runner\ProcessRunner $runner
   *   The process runner instance.
   */
  public function setProcessRunner(ProcessRunner $runner): void {
    $this->processRunner = $runner;
  }

}
