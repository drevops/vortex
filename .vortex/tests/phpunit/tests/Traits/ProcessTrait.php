<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits;

use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait as UpstreamProcessTrait;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Provides process execution functionality.
 */
trait ProcessTrait {

  use UpstreamProcessTrait;

  /**
   * Run a process.
   *
   * @param string $command
   *   The command to run.
   * @param array $arguments
   *   Command arguments.
   * @param array $inputs
   *   Array of inputs for interactive processes.
   * @param array $env
   *   Additional environment variables.
   * @param int $timeout
   *   Process timeout in seconds.
   * @param int $idle_timeout
   *   Process idle timeout in seconds.
   *
   * @return \Symfony\Component\Process\Process
   *   The completed process.
   */
  public function processRun(
    string $command,
    array $arguments = [],
    array $inputs = [],
    array $env = [],
    int $timeout = 60,
    int $idle_timeout = 30,
  ): Process {
    if (preg_match('/[^a-zA-Z0-9_\-\.\/]/', $command)) {
      throw new \InvalidArgumentException(sprintf('Invalid command: %s. Only alphanumeric characters, dashes, underscores, and slashes are allowed.', $command));
    }

    foreach ($arguments as $arg) {
      if (!is_scalar($arg)) {
        throw new \InvalidArgumentException("All arguments must be scalar values.");
      }
    }

    foreach ($env as $env_value) {
      if (!is_scalar($env_value)) {
        throw new \InvalidArgumentException("All environment variables must be scalar values.");
      }
    }

    $cmd = array_merge([$command], $arguments);

    $inputs = empty($inputs) ? NULL : implode(PHP_EOL, $inputs) . PHP_EOL;

    if ($this->process instanceof Process) {
      $this->process->stop();
      $this->process = NULL;
    }

    $this->process = new Process(
      $cmd,
      $this->processCwd,
      $env,
      $inputs,
      $timeout
    );

    $this->process->setIdleTimeout($idle_timeout);

    try {
      $this->process->run(function ($type, $buffer): void {
        // @codeCoverageIgnoreStart
        if ($this->processShowOutput) {
          fwrite(STDOUT, $buffer);
        }
        // @codeCoverageIgnoreEnd
      });
    }
    // @codeCoverageIgnoreStart
    catch (ProcessTimedOutException $processTimedOutException) {
      print 'PROCESS TIMED OUT: ' . PHP_EOL . $processTimedOutException->getMessage() . PHP_EOL;
    }
    catch (\Exception $exception) {
      print 'PROCESS ERROR: ' . PHP_EOL . $exception->getMessage() . PHP_EOL;
    }
    // @codeCoverageIgnoreEnd
    return $this->process;
  }

}
