<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Runner;

/**
 * Interface for classes that use CommandRunner.
 */
interface CommandRunnerAwareInterface {

  /**
   * Get the command runner.
   *
   * @return \DrevOps\VortexCli\Runner\CommandRunner
   *   The command runner instance.
   */
  public function getCommandRunner(): CommandRunner;

  /**
   * Set the command runner.
   *
   * @param \DrevOps\VortexCli\Runner\CommandRunner $runner
   *   The command runner instance.
   */
  public function setCommandRunner(CommandRunner $runner): void;

}
