<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Runner;

use DrevOps\VortexInstaller\Logger\LoggableInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface for command and process runners.
 */
interface RunnerInterface extends LoggableInterface {

  // @see https://tldp.org/LDP/abs/html/exitcodes.html
  public const EXIT_SUCCESS = 0;

  public const EXIT_FAILURE = 1;

  public const EXIT_INVALID = 2;

  public const EXIT_COMMAND_NOT_FOUND = 127;

  /**
   * Run a command.
   *
   * @param string $command
   *   The command to run. Can include arguments (e.g., "git status").
   * @param array<int|string, mixed> $args
   *   Additional command arguments.
   * @param array<string, string> $inputs
   *   Interactive inputs for the command.
   * @param array<string, string> $env
   *   Environment variables.
   * @param \Symfony\Component\Console\Output\OutputInterface|null $output
   *   Output interface. Defaults to STDOUT if NULL.
   *
   * @return self
   *   The runner instance for method chaining.
   */
  public function run(string $command, array $args = [], array $inputs = [], array $env = [], ?OutputInterface $output = NULL): self;

  /**
   * Get the last command that was run.
   */
  public function getCommand(): ?string;

  /**
   * Get the exit code from the last run.
   *
   * @return int<0, 255>
   *   The exit code.
   */
  public function getExitCode(): int;

  /**
   * Get the output from the last run.
   *
   * @param bool $as_array
   *   Whether to return output as array of lines. Defaults to FALSE.
   * @param int|null $lines
   *   Number of lines to return. NULL returns all lines.
   *
   * @return string|array<int, string>
   *   Output as string or array of lines.
   */
  public function getOutput(bool $as_array = FALSE, ?int $lines = NULL): string|array;

  /**
   * Set the working directory.
   *
   * @param string $cwd
   *   The working directory path.
   *
   * @return static
   *   The runner instance for method chaining.
   */
  public function setCwd(string $cwd): static;

  /**
   * Get the working directory.
   *
   * @return string
   *   The working directory path.
   */
  public function getCwd(): string;

}
