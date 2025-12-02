<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Runner;

use DrevOps\VortexInstaller\Logger\FileLogger;
use DrevOps\VortexInstaller\Logger\FileLoggerInterface;
use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract base class for runners.
 */
abstract class AbstractRunner implements RunnerInterface {

  /**
   * The last command that was run.
   */
  protected ?string $command = NULL;

  /**
   * The exit code from the last run.
   *
   * @var int<0, 255>
   */
  protected int $exitCode = 0;

  /**
   * The output from the last run.
   */
  protected string $output = '';

  /**
   * The working directory.
   */
  protected string $cwd = '';

  /**
   * The logger instance.
   */
  protected FileLoggerInterface $logger;

  /**
   * Whether to stream output to console.
   */
  protected bool $shouldStream = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getLogger(): FileLoggerInterface {
    if (!isset($this->logger)) {
      $this->logger = new FileLogger();
    }

    return $this->logger;
  }

  /**
   * Initialize the logger for a command execution.
   *
   * Sets the logger directory and opens a log file for the command.
   *
   * @param string $command
   *   The command name for the log filename.
   * @param array<int, string> $args
   *   Positional arguments to include in the log filename.
   *
   * @return \DrevOps\VortexInstaller\Logger\FileLoggerInterface
   *   The initialized logger instance.
   */
  protected function initLogger(string $command, array $args = []): FileLoggerInterface {
    $logger = $this->getLogger();
    $logger->setDir($this->getCwd());
    $logger->open($command, $args);

    return $logger;
  }

  /**
   * Resolve the output interface, defaulting to ConsoleOutput.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface|null $output
   *   The output interface or NULL to use default.
   *
   * @return \Symfony\Component\Console\Output\OutputInterface
   *   The resolved output interface.
   */
  protected function resolveOutput(?OutputInterface $output): OutputInterface {
    return $output ?? Tui::output();
  }

  /**
   * {@inheritdoc}
   */
  public function setCwd(string $cwd): static {
    $this->cwd = $cwd;
    $this->getLogger()->setDir($cwd);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCwd(): string {
    if ($this->cwd === '') {
      $cwd = getcwd();
      if ($cwd === FALSE) {
        throw new \RuntimeException('Unable to determine current working directory.');
      }
      $this->cwd = $cwd;
      $this->getLogger()->setDir($this->cwd);
    }

    return $this->cwd;
  }

  /**
   * {@inheritdoc}
   */
  public function enableLog(): static {
    $this->getLogger()->enable();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disableLog(): static {
    $this->getLogger()->disable();

    return $this;
  }

  /**
   * Enable streaming output to console.
   *
   * @return static
   *   The runner instance for method chaining.
   */
  public function enableStreaming(): static {
    $this->shouldStream = TRUE;

    return $this;
  }

  /**
   * Disable streaming output to console.
   *
   * When disabled, output is still captured but not written to console.
   *
   * @return static
   *   The runner instance for method chaining.
   */
  public function disableStreaming(): static {
    $this->shouldStream = FALSE;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommand(): ?string {
    return $this->command;
  }

  /**
   * Get the exit code from the last run.
   *
   * @return int<0, 255>
   *   The exit code.
   */
  public function getExitCode(): int {
    if ($this->exitCode < 0 || $this->exitCode > 255) {
      throw new \RuntimeException(sprintf('Exit code %d is out of valid range (0-255).', $this->exitCode));
    }

    return $this->exitCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutput(bool $as_array = FALSE, ?int $lines = NULL): string | array {
    $output_lines = explode(PHP_EOL, $this->output);

    if ($lines !== NULL) {
      $output_lines = array_slice($output_lines, 0, $lines);
    }

    if ($as_array) {
      return $output_lines;
    }

    return implode(PHP_EOL, $output_lines);
  }

  /**
   * Reset state before a new run.
   */
  protected function reset(): void {
    $this->command = NULL;
    $this->output = '';
    $this->exitCode = RunnerInterface::EXIT_SUCCESS;
  }

  /**
   * Parse a command string into an array of parts.
   *
   * Handles quoted arguments and escaping properly. Supports both single
   * and double quotes. Also supports the end-of-options marker (--) which
   * stops option parsing and treats all subsequent tokens as positional
   * arguments.
   *
   * Note: This parser intentionally allows backslash escaping inside single
   * quotes (e.g., 'It\'s working'), which deviates from POSIX shell behavior
   * where backslashes are literal inside single quotes. This provides more
   * intuitive escaping for users.
   *
   * @param string $command
   *   The command string to parse.
   *
   * @return array<int, string>
   *   Array with command as first element and arguments as subsequent elements.
   */
  protected function parseCommand(string $command): array {
    $command = trim($command);
    if (empty($command)) {
      throw new \InvalidArgumentException('Command cannot be empty.');
    }

    $parts = [];
    $current = '';
    $in_quotes = FALSE;
    $quote_char = '';
    $escaped = FALSE;
    $length = strlen($command);
    $has_content = FALSE;
    $end_of_options_found = FALSE;

    for ($i = 0; $i < $length; $i++) {
      $char = $command[$i];

      if ($escaped) {
        $current .= $char;
        $escaped = FALSE;
        $has_content = TRUE;
        continue;
      }

      if ($char === '\\') {
        $escaped = TRUE;
        continue;
      }

      if (!$in_quotes && ($char === '"' || $char === "'")) {
        $in_quotes = TRUE;
        $quote_char = $char;
        $has_content = TRUE;
        continue;
      }

      if ($in_quotes && $char === $quote_char) {
        $in_quotes = FALSE;
        $quote_char = '';
        continue;
      }

      if (!$in_quotes && ($char === ' ' || $char === "\t")) {
        if ($current !== '' || $has_content) {
          // Check for end-of-options marker (--) only if not already found
          // and not inside quotes.
          if (!$end_of_options_found && $current === '--') {
            $end_of_options_found = TRUE;
            // Add the -- marker to the parts array so it reaches the command.
            $parts[] = $current;
            $current = '';
            $has_content = FALSE;
            continue;
          }

          $parts[] = $current;
          $current = '';
          $has_content = FALSE;
        }
        continue;
      }

      $current .= $char;
      $has_content = TRUE;
    }

    if ($in_quotes) {
      throw new \InvalidArgumentException('Unclosed quote in command string.');
    }

    if ($escaped) {
      throw new \InvalidArgumentException('Trailing escape character in command string.');
    }

    if ($current !== '' || $has_content) {
      $parts[] = $current;
    }

    return $parts;
  }

  /**
   * Build a command string for display or logging.
   *
   * Produces a shell-safe command string that can be copy-pasted.
   * Arguments containing spaces or special characters are properly quoted.
   *
   * @param string $command
   *   The base command.
   * @param array<int|string, mixed> $args
   *   Command arguments.
   * @param array<int|string, mixed> $opts
   *   Command options.
   *
   * @return string
   *   The formatted command string.
   */
  protected function buildCommandString(string $command, array $args = [], array $opts = []): string {
    $parts = [$command];

    $formatted_args = $this->formatArgs($args);
    $formatted_opts = $this->formatArgs($opts);

    foreach ($formatted_args as $formatted_arg) {
      $parts[] = $this->quoteArgument($formatted_arg);
    }

    foreach ($formatted_opts as $formatted_opt) {
      $parts[] = $this->quoteArgument($formatted_opt);
    }

    return implode(' ', $parts);
  }

  /**
   * Quote an argument if it contains special characters.
   *
   * @param string $argument
   *   The argument to quote.
   *
   * @return string
   *   The quoted argument if needed, otherwise the original.
   */
  protected function quoteArgument(string $argument): string {
    // If argument is empty, return empty quoted string.
    if ($argument === '') {
      return "''";
    }

    // Check if argument needs quoting (contains spaces, quotes, or shell
    // special chars).
    if (preg_match('/[\s"\'\\\\$`!*?#~<>|;&(){}[\]]/', $argument)) {
      // Use single quotes and escape any single quotes within.
      $escaped = str_replace("'", "'\\''", $argument);
      return "'" . $escaped . "'";
    }

    return $argument;
  }

  /**
   * Format arguments for display or logging.
   *
   * @param array<int|string, mixed> $args
   *   The arguments to format.
   *
   * @return array<int, string>
   *   Formatted arguments as strings.
   */
  protected function formatArgs(array $args): array {
    $formatted = [];

    foreach ($args as $key => $value) {
      if (is_int($key)) {
        // Positional argument.
        if (is_bool($value)) {
          if ($value) {
            $formatted[] = '1';
          }
        }
        else {
          $formatted[] = (string) $value;
        }
      }
      elseif (is_bool($value)) {
        // Named argument/option.
        if ($value) {
          $formatted[] = $key;
        }
      }
      else {
        $formatted[] = $key . '=' . $value;
      }
    }

    return $formatted;
  }

}
