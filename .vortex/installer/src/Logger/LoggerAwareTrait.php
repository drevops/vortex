<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Logger;

/**
 * Provides FileLogger dependency injection.
 */
trait LoggerAwareTrait {

  /**
   * The logger.
   */
  protected ?FileLoggerInterface $logger = NULL;

  /**
   * Get the logger.
   *
   * Factory method that returns existing logger or creates new one.
   *
   * @return \DrevOps\VortexInstaller\Logger\FileLoggerInterface
   *   The logger instance.
   */
  public function getLogger(): FileLoggerInterface {
    return $this->logger ??= new FileLogger();
  }

  /**
   * Set the logger.
   *
   * Allows dependency injection for testing.
   *
   * @param \DrevOps\VortexInstaller\Logger\FileLoggerInterface $logger
   *   The logger instance.
   */
  public function setLogger(FileLoggerInterface $logger): void {
    $this->logger = $logger;
  }

}
