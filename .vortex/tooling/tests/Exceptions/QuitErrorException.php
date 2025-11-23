<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Exceptions;

/**
 * Exception thrown when mocked quit() is called with non-zero exit code.
 *
 * This allows tests to verify exit codes without actually terminating.
 */
class QuitErrorException extends \Exception {

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
    if ($code === 0) {
      throw new \InvalidArgumentException('QuitErrorException can only be used for non-zero exit codes.');
    }

    parent::__construct('quit() was called with non-zero exit code: ' . $code, $code);
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
