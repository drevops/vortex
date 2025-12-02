<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Runner;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Runner for shell commands via Symfony Process.
 */
class ProcessRunner extends AbstractRunner implements ExecutableFinderAwareInterface {

  use ExecutableFinderAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function run(string $command, array $args = [], array $inputs = [], array $env = [], ?OutputInterface $output = NULL): self {
    set_time_limit(0);

    $this->reset();

    // Parse and resolve the command.
    [$base_command, $parsed_args] = $this->resolveCommand($command);

    $all_args = $this->prepareArguments($parsed_args, $args);

    $this->validateEnvironmentVars($env);

    // Build full command array.
    $cmd = array_merge([$base_command], $all_args);

    // Store command string for logging with proper quoting.
    $this->command = $this->buildCommandString($base_command, $all_args);

    $logger = $this->initLogger($base_command, $parsed_args);
    $output = $this->resolveOutput($output);

    // Prepare inputs for interactive processes.
    $input_string = empty($inputs) ? NULL : implode(PHP_EOL, $inputs) . PHP_EOL;

    $process = new Process($cmd, $this->getCwd(), $env ?: NULL, $input_string);
    $process->setTimeout(NULL);
    $process->setIdleTimeout(NULL);

    $process->run(function ($type, string|iterable $buffer) use ($logger, $output): void {
      $buffer = is_iterable($buffer) ? implode("\n", (array) $buffer) : $buffer;
      $this->output = $buffer;
      if ($this->shouldStream) {
        $output->write($buffer);
      }
      $logger->write($buffer);
    });

    $logger->close();

    $exit_code = $process->getExitCode();

    if ($exit_code < 0 || $exit_code > 255) {
      throw new \RuntimeException('Command exited with invalid exit code: ' . $exit_code);
    }

    match ($exit_code) {
      Command::SUCCESS => $this->exitCode = self::EXIT_SUCCESS,
      Command::FAILURE => $this->exitCode = self::EXIT_FAILURE,
      127 => $this->exitCode = self::EXIT_COMMAND_NOT_FOUND,
      default => $this->exitCode = self::EXIT_INVALID,
    };

    $this->exitCode = $exit_code;

    return $this;
  }

  /**
   * Parse and resolve the command, validating it exists.
   *
   * @param string $command
   *   The command string to parse.
   *
   * @return array{0: string, 1: array<int, string>}
   *   Array with [resolved_command_path, parsed_arguments].
   *
   * @throws \InvalidArgumentException
   *   When command contains invalid characters or cannot be found.
   */
  protected function resolveCommand(string $command): array {
    $parsed = $this->parseCommand($command);
    $base_command = array_shift($parsed);

    // Defensive check: prevent using 'command' utility.
    if ($base_command === 'command') {
      throw new \InvalidArgumentException('Using the "command" utility is not allowed. Use Symfony\Component\Process\ExecutableFinder to check if a command exists instead.');
    }

    // Validate the base command contains only allowed characters.
    if (preg_match('/[^a-zA-Z0-9_\-.\/]/', (string) $base_command)) {
      throw new \InvalidArgumentException(sprintf('Invalid command: %s. Only alphanumeric characters, dots, dashes, underscores and slashes are allowed.', $base_command));
    }

    // If command is a path (contains /), check if it exists directly.
    if (str_contains((string) $base_command, '/')) {
      $resolved = $base_command;
      // Check relative to cwd if not absolute.
      if (!str_starts_with((string) $base_command, '/')) {
        $full_path = $this->getCwd() . '/' . $base_command;
        if (is_executable($full_path)) {
          $resolved = $full_path;
        }
      }
    }
    else {
      // Use ExecutableFinder for commands without path.
      $resolved = $this->getExecutableFinder()->find($base_command);

      if ($resolved === NULL) {
        throw new \InvalidArgumentException(sprintf('Command not found: %s. Ensure the command is installed and available in PATH.', $base_command));
      }
    }

    return [$resolved, $parsed];
  }

  /**
   * Prepare arguments by merging and validating them.
   *
   * @param array<int, string> $parsed_args
   *   Arguments parsed from the command string.
   * @param array<int|string, mixed> $additional_args
   *   Additional arguments passed to run().
   *
   * @return array<int, string>
   *   Merged and validated arguments as strings.
   *
   * @throws \InvalidArgumentException
   *   When an argument is not a scalar value.
   */
  protected function prepareArguments(array $parsed_args, array $additional_args): array {
    $all_args = array_merge($parsed_args, $this->formatArgs($additional_args));

    foreach ($all_args as $key => &$arg) {
      if (!is_scalar($arg)) {
        $value_repr = get_debug_type($arg);
        throw new \InvalidArgumentException(sprintf('Argument at index "%s" must be a scalar value, %s given.', $key, $value_repr));
      }
      $arg = (string) $arg;
    }
    unset($arg);

    return $all_args;
  }

  /**
   * Validate environment variables are scalar values.
   *
   * @param array<string, mixed> $env
   *   Environment variables to validate.
   *
   * @throws \InvalidArgumentException
   *   When an environment variable is not a scalar value.
   */
  protected function validateEnvironmentVars(array $env): void {
    foreach ($env as $key => $env_value) {
      if (!is_scalar($env_value)) {
        $value_repr = get_debug_type($env_value);
        throw new \InvalidArgumentException(sprintf('Environment variable "%s" must be a scalar value, %s given.', $key, $value_repr));
      }
    }
  }

}
