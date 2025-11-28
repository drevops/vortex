<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Logger;

/**
 * Interface for file-based command execution loggers.
 */
interface FileLoggerInterface extends LoggerInterface {

  /**
   * Get the log file path.
   *
   * @return string|null
   *   The log file path, or NULL if not opened or logging disabled.
   */
  public function getPath(): ?string;

  /**
   * Set the base directory for log files.
   *
   * @param string $dir
   *   The base directory path.
   *
   * @return static
   *   The logger instance for method chaining.
   */
  public function setDir(string $dir): static;

  /**
   * Get the base directory for log files.
   *
   * @return string
   *   The base directory path.
   */
  public function getDir(): string;

}
