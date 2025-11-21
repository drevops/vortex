<?php

/**
 * @file
 * Helper functions for Vortex tooling scripts.
 *
 * This file provides reusable PHP helper functions for Vortex notification
 * and utility scripts, enabling consistent behavior across all tooling.
 *
 * ## Why We Use These Helpers
 *
 * These helper functions serve several critical purposes:
 *
 * 1. **Consistency**: Standardized output formatting (info, task, pass, fail)
 *    ensures all Vortex scripts produce uniform, recognizable messages.
 *
 * 2. **Reusability**: Common operations (HTTP requests, environment loading,
 *    token replacement) are centralized to avoid code duplication.
 *
 * 3. **Testability**: All functions are designed to be mockable and testable,
 *    with comprehensive unit tests ensuring reliability.
 *
 * 4. **Maintainability**: Changes to core functionality (e.g., output
 *    formatting, HTTP client behavior) only need to be made in one place.
 */

declare(strict_types=1);

namespace DrevOps\VortexTooling;

// @codeCoverageIgnoreStart
load_dotenv(['.env', '.env.local']);
// @codeCoverageIgnoreEnd

/**
 * Check if current script has an override and execute it if found.
 *
 * Call this at the start of your script to allow it to be overridden.
 *
 * @param string $name
 *   Name of current script.
 */
function execute_override(string $name): void {
  $dir = getenv('VORTEX_TOOLING_CUSTOM_DIR');
  if ($dir) {
    $path = $dir . DIRECTORY_SEPARATOR . $name;
    if (file_exists($path) && is_executable($path)) {
      passthru(sprintf('"%s"', $path), $exit_code);
      quit($exit_code);
    }
  }
}

/**
 * Load environment variables from .env and .env.local files.
 *
 * @param array<int,string> $env_files
 *   Array of environment file paths to load.
 */
function load_dotenv(array $env_files = ['.env']): void {
  foreach ($env_files as $env_file) {
    if (file_exists($env_file)) {
      $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

      if ($lines === FALSE) {
        // @codeCoverageIgnoreStart
        continue;
        // @codeCoverageIgnoreEnd
      }

      foreach ($lines as $line) {
        // Skip comments.
        if (str_starts_with(trim($line), '#')) {
          continue;
        }

        // Parse KEY=VALUE format.
        if (str_contains($line, '=')) {
          [$key, $value] = explode('=', $line, 2);
          $key = trim($key);
          $value = trim($value);

          // Remove quotes if present.
          if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
          }

          putenv(sprintf('%s=%s', $key, $value));
        }
      }
    }
  }
}

/**
 * Output a note message.
 *
 * @param string $format
 *   Format string for sprintf().
 * @param bool|float|int|string|null ...$args
 *   Arguments for sprintf().
 */
function note(string $format, ...$args): void {
  echo sprintf('       %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Output a task message.
 *
 * @param string $format
 *   Format string for sprintf().
 * @param bool|float|int|string|null ...$args
 *   Arguments for sprintf().
 */
function task(string $format, ...$args): void {
  echo _supports_color() ?
    "\033[34m[TASK] " . sprintf($format, ...$args) . "\033[0m\n" :
    sprintf('[TASK] %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Output an info message.
 *
 * @param string $format
 *   Format string for sprintf().
 * @param bool|float|int|string|null ...$args
 *   Arguments for sprintf().
 */
function info(string $format, ...$args): void {
  echo _supports_color() ?
    "\033[36m[INFO] " . sprintf($format, ...$args) . "\033[0m\n" :
    sprintf('[INFO] %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Output a success message.
 *
 * @param string $format
 *   Format string for sprintf().
 * @param bool|float|int|string|null ...$args
 *   Arguments for sprintf().
 */
function pass(string $format, ...$args): void {
  echo _supports_color() ?
    "\033[32m[ OK ] " . sprintf($format, ...$args) . "\033[0m\n" :
    sprintf('[ OK ] %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Output a failure message and do not exit.
 *
 * @param string $format
 *   Format string for sprintf().
 * @param bool|float|int|string|null ...$args
 *   Arguments for sprintf().
 */
function fail_no_exit(string $format, ...$args): void {
  echo _supports_color() ?
    "\033[31m[FAIL] " . sprintf($format, ...$args) . "\033[0m\n" :
    sprintf('[FAIL] %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Output a failure message.
 *
 * @param string $format
 *   Format string for sprintf().
 * @param bool|float|int|string|null ...$args
 *   Arguments for sprintf().
 */
function fail(string $format, ...$args): void {
  fail_no_exit($format, ...$args);
  quit(1);
}

/**
 * Require an environment variable to be set.
 *
 * @param string $name
 *   Environment variable name.
 * @param string|null $message
 *   Custom error message (optional).
 */
function validate_variable(string $name, ?string $message = NULL): void {
  $value = getenv($name);
  if ($value === FALSE || $value === '') {
    fail($message ?? sprintf('Missing required value for variable %s', $name));
  }
}

/**
 * Require a command to be available.
 *
 * @param string $command
 *   Command name.
 */
function validate_command(string $command): void {
  exec(sprintf('command -v %s 2>/dev/null', $command), $output, $code);
  if ($code !== 0) {
    fail(sprintf("Command '%s' is not available", $command));
  }
}

/**
 * Replace tokens in a string.
 *
 * @param string $template
 *   Template string with tokens like %project%, %label%, etc.
 * @param array<string,string> $replacements
 *   Array of token => value pairs.
 *
 * @return string
 *   String with tokens replaced.
 */
function replace_tokens(string $template, array $replacements): string {
  $search = [];
  $replace = [];

  foreach ($replacements as $token => $value) {
    $search[] = sprintf('%%%s%%', $token);
    $replace[] = $value;
  }

  return str_replace($search, $replace, $template);
}

/**
 * Check if debug mode is enabled.
 */
function is_debug(): bool {
  // @codeCoverageIgnoreStart
  return getenv('VORTEX_DEBUG') === '1';
  // @codeCoverageIgnoreEnd
}

/**
 * Check if terminal supports colors.
 */
function _supports_color(): bool {
  return getenv('TERM') === 'dumb' || getenv('TERM') === FALSE ? FALSE : function_exists('posix_isatty') && @posix_isatty(STDOUT);
}

/**
 * Perform a GET request.
 *
 * @param string $url
 *   URL to request.
 * @param array<int, string> $headers
 *   Array of HTTP headers.
 * @param int $timeout
 *   Request timeout in seconds.
 *
 * @return array{ok: bool, status: int, body: string|false, error: string|null, info: array<string, mixed>}
 *   Array with keys:
 *   - ok: TRUE if request was successful (HTTP < 400), FALSE otherwise
 *   - status: HTTP status code
 *   - body: Response body
 *   - error: Error message if any
 *   - info: Request info array
 */
function request_get(string $url, array $headers = [], int $timeout = 10): array {
  return request($url, [
    'method' => 'GET',
    'headers' => $headers,
    'timeout' => $timeout,
  ]);
}

/**
 * Perform a POST request.
 *
 * @param string $url
 *   URL to request.
 * @param mixed $body
 *   Request body.
 * @param array<int, string> $headers
 *   Array of HTTP headers.
 * @param int $timeout
 *   Request timeout in seconds.
 *
 * @return array{ok: bool, status: int, body: string|false, error: string|null, info: array<string, mixed>}
 *   Array with keys:
 *   - ok: TRUE if request was successful (HTTP < 400), FALSE otherwise
 *   - status: HTTP status code
 *   - body: Response body
 *   - error: Error message if any
 *   - info: Request info array
 */
function request_post(string $url, $body = NULL, array $headers = [], int $timeout = 10): array {
  return request($url, [
    'method' => 'POST',
    'body' => $body,
    'headers' => $headers,
    'timeout' => $timeout,
  ]);
}

/**
 * Perform an HTTP request.
 *
 * @param string $url
 *   URL to request.
 * @param array{method?: string, headers?: array<int, string>, body?: mixed, timeout?: int} $options
 *   Array of options:
 *   - method: HTTP method (GET, POST, etc.)
 *   - headers: Array of HTTP headers
 *   - body: Request body
 *   - timeout: Request timeout in seconds.
 *
 * @return array{ok: bool, status: int, body: string|false, error: string|null, info: array<string, mixed>}
 *   Array with keys:
 *   - ok: TRUE if request was successful (HTTP < 400), FALSE otherwise
 *   - status: HTTP status code
 *   - body: Response body
 *   - error: Error message if any
 *   - info: Request info array
 */
function request(string $url, array $options = []): array {
  // @codeCoverageIgnoreStart
  if (!function_exists('curl_init')) {
    fail('curl extension is not available.');
  }
  // @codeCoverageIgnoreEnd
  $ch = curl_init($url);

  try {
    /** @var array<int, mixed> $opts */
    $opts = [
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_TIMEOUT => $options['timeout'] ?? 10,
    ];

    if (isset($options['method'])) {
      $opts[CURLOPT_CUSTOMREQUEST] = strtoupper((string) $options['method']);
    }

    if (isset($options['headers'])) {
      $opts[CURLOPT_HTTPHEADER] = $options['headers'];
    }

    if (isset($options['body'])) {
      $opts[CURLOPT_POSTFIELDS] = $options['body'];
    }

    curl_setopt_array($ch, $opts);

    // With CURLOPT_RETURNTRANSFER, curl_exec returns string|false.
    /** @var string|false $body */
    $body = curl_exec($ch);
    $error = curl_errno($ch) ? curl_error($ch) : NULL;
    $info = curl_getinfo($ch);

    // Handle curl_getinfo failure.
    if ($info === FALSE) {
      // @codeCoverageIgnoreStart
      $info = ['http_code' => 0];
      // @codeCoverageIgnoreEnd
    }

    return [
      'ok' => !$error && ($info['http_code'] < 400),
      'status' => $info['http_code'],
      'body' => $body,
      'error' => $error,
      'info' => $info,
    ];
  }
  finally {
    // CurlHandle objects are automatically freed when they go out of scope
    // (PHP 8.0+), so explicit curl_close() is no longer needed.
    // The unset here ensures the handle goes out of scope immediately.
    unset($ch);
  }
}

// Never run the real quit() function during tests. This also avoids bleeding
// into global namespace when running multiple tests that share the same
// test process.
// Note that this replicates the behaviour of global built-in functions
// like passthru() and exec() which are *not defined in this namespace*. We only
// defined quit() in a namespace because mocking of global functions can only
// be done if they are defined in a namespace.
// @codeCoverageIgnoreStart
if (!function_exists('DrevOps\VortexTooling\quit') && !class_exists('PHPUnit\\Framework\\TestCase')) {

  /**
   * Exit script with given code.
   *
   * Wrapper around exit() to allow mocking in tests since exit() cannot be
   * directly mocked despite being a function in PHP 8.4+.
   *
   * @param int $code
   *   Exit code (0 for success, non-zero for error).
   */
  function quit(int $code = 0): void {
    exit($code);
  }

}
// @codeCoverageIgnoreEnd
