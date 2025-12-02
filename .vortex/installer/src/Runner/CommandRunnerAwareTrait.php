<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Runner;

/**
 * Provides CommandRunner dependency injection.
 */
trait CommandRunnerAwareTrait {

  /**
   * The command runner.
   */
  protected ?CommandRunner $commandRunner = NULL;

  /**
   * Get the command runner.
   *
   * Factory method that returns existing runner or creates new one.
   * Requires getApplication() method from Symfony Command class.
   *
   * @return \DrevOps\VortexInstaller\Runner\CommandRunner
   *   The command runner instance.
   */
  public function getCommandRunner(): CommandRunner {
    // @phpstan-ignore-next-line
    return $this->commandRunner ??= new CommandRunner($this->getApplication());
  }

  /**
   * Set the command runner.
   *
   * Allows dependency injection for testing.
   *
   * @param \DrevOps\VortexInstaller\Runner\CommandRunner $runner
   *   The command runner instance.
   */
  public function setCommandRunner(CommandRunner $runner): void {
    $this->commandRunner = $runner;
  }

}
