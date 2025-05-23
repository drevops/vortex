<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits;

/**
 * Provides logging functionality for tests.
 */
trait LoggerTrait {

  public static function log(string $message): void {
    fwrite(STDERR, PHP_EOL . $message . PHP_EOL);
  }

  public static function logStepStart(?string $message = NULL): void {
    if ($message === NULL) {
      $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
      $message = $trace[1]['function'] ?? 'unknown';
    }

    $message .= PHP_EOL . 'Current working directory: ' . getcwd() . PHP_EOL;

    static::logBox('▶', 'START', $message);
  }

  public static function logStepFinish(?string $message = NULL): void {
    if ($message === NULL) {
      $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
      $message = $trace[1]['function'] ?? 'unknown';
    }
    static::logBox('■', 'DONE', $message);
  }

  public static function logSubstep(string $message): void {
    static::log('  ▶▶ ' . $message);
  }

  public static function logFile(string $path, ?string $message = NULL): void {
    if (!file_exists($path)) {
      throw new \InvalidArgumentException(sprintf('File %s does not exist.', $path));
    }

    $content = file_get_contents($path);
    if ($content === FALSE) {
      throw new \RuntimeException(sprintf('Failed to read file %s.', $path));
    }

    $message = $message ? $message . ' (' . $path . ')' : $path;

    static::logBox('📄', 'FILE START', $message);
    static::log($content);
    static::logBox('📄', 'FILE END', $message);
  }

  public static function logBox(string $symbol, string $label, string $message): void {
    $message = trim($message);
    $content = sprintf('%s %s %s', $symbol, $label, $message);

    $lines = explode(PHP_EOL, $content);
    $max_length = max(array_map('mb_strlen', $lines)) + 2;

    $top = '┌' . str_repeat('─', $max_length) . '┐';
    $bottom = '└' . str_repeat('─', $max_length) . '┘';

    fwrite(STDERR, PHP_EOL . $top . PHP_EOL);

    foreach ($lines as $line) {
      $padding = str_repeat(' ', $max_length - mb_strlen($line) - 2);
      fwrite(STDERR, sprintf('│ %s%s │', $line, $padding) . PHP_EOL);
    }

    fwrite(STDERR, $bottom . PHP_EOL);
  }

}
