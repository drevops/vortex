<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits;

use AlexSkrypnyk\PhpunitHelpers\Traits\LoggerTrait as UpstreamLoggerTrait;

/**
 * Provides logging functionality for tests.
 */
trait LoggerTrait {

  use UpstreamLoggerTrait;

  public static function logStepStart(?string $message = NULL): void {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $step = $trace[1]['function'] ?? 'unknown';

    static::logSection('STEP START | ' . $step, $message, FALSE, 40);
    fwrite(STDERR, PHP_EOL);
  }

  public static function logStepFinish(?string $message = NULL): void {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $step = $trace[1]['function'] ?? 'unknown';

    static::logSection('STEP DONE | ' . $step, $message, FALSE, 40);
    fwrite(STDERR, PHP_EOL);
  }

  public static function logSubstep(string $message): void {
    fwrite(STDERR, '  --> ' . $message . PHP_EOL);
  }

}
