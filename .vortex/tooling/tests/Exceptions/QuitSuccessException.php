<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Exceptions;

/**
 * Exception thrown when mocked quit() is called with exit code 0.
 *
 * This allows tests to verify exit codes without actually terminating.
 */
class QuitSuccessException extends \Exception {

  /**
   * Create exception with exit code.
   *
   * @param int $code
   *   The exit code.
   * @param string $output
   *   The captured output.
   */
  public function __construct(
    int $code,
    protected string $output = '',
  ) {
    if ($code !== 0) {
      throw new \InvalidArgumentException('QuitSuccessException can only be used for exit code 0.');
    }

    parent::__construct('quit() was called with exit code 0', $code);
  }

  /**
   * Gets the captured output.
   *
   * @return string
   *   The captured output.
   */
  public function getOutput(): string {
    return $this->output;
  }

}
