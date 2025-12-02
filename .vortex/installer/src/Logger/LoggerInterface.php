<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Logger;

/**
 * Generic interface for command execution loggers.
 */
interface LoggerInterface {

  /**
   * Open log for a command.
   *
   * @param string $command
   *   The base command name.
   * @param array<int, string> $args
   *   Command arguments (positional, not options).
   *
   * @return bool
   *   TRUE if log was opened, FALSE if logging is disabled.
   */
  public function open(string $command, array $args = []): bool;

  /**
   * Write content to the log.
   *
   * @param string $content
   *   Content to write.
   */
  public function write(string $content): void;

  /**
   * Close the log.
   */
  public function close(): void;

  /**
   * Check if logging is enabled.
   *
   * @return bool
   *   TRUE if logging is enabled.
   */
  public function isEnabled(): bool;

  /**
   * Enable logging.
   *
   * @return static
   *   The logger instance for method chaining.
   */
  public function enable(): static;

  /**
   * Disable logging.
   *
   * @return static
   *   The logger instance for method chaining.
   */
  public function disable(): static;

}
