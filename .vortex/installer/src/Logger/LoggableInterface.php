<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Logger;

/**
 * Interface for classes that support logging capability.
 */
interface LoggableInterface {

  /**
   * Enable logging.
   *
   * @return static
   *   The instance for method chaining.
   */
  public function enableLog(): static;

  /**
   * Disable logging.
   *
   * @return static
   *   The instance for method chaining.
   */
  public function disableLog(): static;

  /**
   * Get the logger instance.
   *
   * @return \DrevOps\VortexInstaller\Logger\LoggerInterface
   *   The logger instance.
   */
  public function getLogger(): LoggerInterface;

}
