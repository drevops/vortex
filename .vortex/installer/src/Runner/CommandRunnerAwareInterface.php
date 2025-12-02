<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Runner;

/**
 * Interface for classes that use CommandRunner.
 */
interface CommandRunnerAwareInterface {

  /**
   * Get the command runner.
   *
   * @return \DrevOps\VortexInstaller\Runner\CommandRunner
   *   The command runner instance.
   */
  public function getCommandRunner(): CommandRunner;

  /**
   * Set the command runner.
   *
   * @param \DrevOps\VortexInstaller\Runner\CommandRunner $runner
   *   The command runner instance.
   */
  public function setCommandRunner(CommandRunner $runner): void;

}
