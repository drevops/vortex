<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Logger;

/**
 * Interface for classes that use FileLogger.
 */
interface LoggerAwareInterface {

  /**
   * Get the logger.
   *
   * @return \DrevOps\VortexInstaller\Logger\FileLoggerInterface
   *   The logger instance.
   */
  public function getLogger(): FileLoggerInterface;

  /**
   * Set the logger.
   *
   * @param \DrevOps\VortexInstaller\Logger\FileLoggerInterface $logger
   *   The logger instance.
   */
  public function setLogger(FileLoggerInterface $logger): void;

}
