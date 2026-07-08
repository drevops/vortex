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
 * Get environment variable with fallback and default value.
 *
 * Checks multiple environment variable names in order and returns the first
 * non-empty value. The last argument is used as the default value if all
 * environment variables are empty or undefined.
 *
 * Empty strings are considered as unset values.
 *
 * @param string ...$args
 *   Variable names to check, with the last argument being the default value.
 *
 * @return string
 *   The first non-empty environment variable value or the default.
 *
 * @code
 * // Check SPECIFIC, then GENERIC, fallback to 'default'
 * $value = getenv_default('SPECIFIC_VAR', 'GENERIC_VAR', 'default');
 *
 * // Check single var with default
 * $value = getenv_default('MY_VAR', 'default');
 * @endcode
 */
// @phpstan-ignore-next-line return.unusedType
function getenv_default(...$args): string|null|false {
  if (count($args) < 2) {
    throw new \InvalidArgumentException('getenv_default() requires at least 2 arguments: one or more variable names and a default value');
  }

  // Last argument is the default value.
  $default = array_pop($args);

  // Check each environment variable.
  foreach ($args as $var_name) {
    $value = getenv($var_name);
    if ($value !== FALSE && $value !== '') {
      return $value;
    }
  }

  return $default;
}

/**
 * Get required environment variable with fallback support.
 *
 * Checks multiple environment variable names in order and returns the first
 * non-empty value. If all variables are empty or undefined, fails with an
 * error listing all checked variables.
 *
 * Empty strings are considered as unset values.
 *
 * @param string ...$var_names
 *   Variable names to check.
 *
 * @return string|never
 *   The first non-empty environment variable value.
 *
 * @code
 * // Check SPECIFIC, then GENERIC (at least one must be set)
 * $value = getenv_required('SPECIFIC_VAR', 'GENERIC_VAR');
 *
 * // Check single required var
 * $value = getenv_required('REQUIRED_VAR');
 * @endcode
 */
function getenv_required(...$var_names): string {
  if (count($var_names) < 1) {
    throw new \InvalidArgumentException('getenv_required() requires at least 1 argument');
  }

  // Check each environment variable.
  foreach ($var_names as $var_name) {
    $value = getenv($var_name);
    if ($value !== FALSE && $value !== '') {
      return $value;
    }
  }

  // None found, fail with error.
  $var_list = implode(', ', $var_names);
  FAIL('Missing required value for %s', $var_list);

  // Never reached, but satisfies return type.
  // @codeCoverageIgnoreStart
  return '';
  // @codeCoverageIgnoreEnd
}

/**
 * Get a comma-separated environment variable as a list of values.
 *
 * Checks multiple environment variable names in order and splits the first
 * non-empty value on commas into trimmed, non-empty items. The last argument
 * is used as the default value if all environment variables are empty or
 * undefined, as in getenv_default().
 *
 * @param string ...$args
 *   Variable names to check, with the last argument being the default value.
 *
 * @return array<int, string>
 *   The list of values.
 *
 * @code
 * // "main, develop" -> ['main', 'develop']
 * $branches = getenv_list('SPECIFIC_BRANCHES', 'GENERIC_BRANCHES', '');
 * @endcode
 */
function getenv_list(...$args): array {
  $value = (string) getenv_default(...$args);

  return array_values(array_filter(array_map(trim(...), explode(',', $value)), static fn(string $item): bool => $item !== ''));
}

/**
 * Output a note message.
 *
 * @param string $format
 *   Format string for sprintf().
 * @param bool|float|int|string|null ...$args
 *   Arguments for sprintf().
 */
// phpcs:ignore Drupal.NamingConventions.ValidFunctionName.InvalidName -- uppercase for output visibility.
function NOTE(string $format, ...$args): void {
  echo sprintf('       %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Announce a task, or run its body and report the outcome.
 *
 * With only a message, announces the task - a caller then follows with its own
 * pass()/fail(). With a done message and a body, it runs the body under a live
 * [TASK] line (the body may emit progress dots via progress_dot()/
 * sleep_progress() while it works), then reports [ OK ] with the done message.
 * The body signals failure by throwing: the thrown message is reported as
 * [FAIL]. A fatal task then exits the script; a non-fatal one returns NULL so a
 * best-effort loop can carry on to its next item. This keeps each task's
 * intent, work and outcome in one place and guarantees every task ends with a
 * status.
 *
 * @param string $doing
 *   The present-tense task message, e.g. 'Downloading the backup.'.
 * @param string|\Closure|null $done
 *   The success message reported when a body completes. A closure receives the
 *   body's return value and produces the message, for outcomes known only once
 *   the work is done.
 * @param callable|null $body
 *   The work to perform; throw to fail the task with the thrown message.
 * @param bool $fatal
 *   Whether a thrown failure exits the script. TRUE (default) reports [FAIL]
 *   and exits; FALSE reports [FAIL] and returns NULL so the caller continues.
 *
 * @return mixed
 *   Whatever the body returns, or NULL when announcing only or when a non-fatal
 *   body fails.
 */
// phpcs:ignore Drupal.NamingConventions.ValidFunctionName.InvalidName -- uppercase for output visibility.
function TASK(string $doing, string|\Closure|null $done = NULL, ?callable $body = NULL, bool $fatal = TRUE): mixed {
  $color = term_supports_color();

  if ($body === NULL) {
    echo $color ? "\033[34m[TASK] " . $doing . "\033[0m" . PHP_EOL : '[TASK] ' . $doing . PHP_EOL;

    return NULL;
  }

  echo $color ? "\033[34m[TASK] " . $doing : '[TASK] ' . $doing;

  try {
    $result = $body();
    echo ($color ? "\033[0m" : '') . PHP_EOL;
    PASS('%s', $done instanceof \Closure ? (string) $done($result) : (string) $done);

    return $result;
  }
  catch (\Throwable $e) {
    echo ($color ? "\033[0m" : '') . PHP_EOL;

    if (!$fatal) {
      fail_no_exit('%s', $e->getMessage());

      return NULL;
    }

    FAIL('%s', $e->getMessage());
  }

  // @codeCoverageIgnoreStart
  // Unreachable: fail() above terminates the script.
  return NULL;
  // @codeCoverageIgnoreEnd
}

/**
 * Output an info message.
 *
 * @param string $format
 *   Format string for sprintf().
 * @param bool|float|int|string|null ...$args
 *   Arguments for sprintf().
 */
// phpcs:ignore Drupal.NamingConventions.ValidFunctionName.InvalidName -- uppercase for output visibility.
function INFO(string $format, ...$args): void {
  echo term_supports_color() ?
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
// phpcs:ignore Drupal.NamingConventions.ValidFunctionName.InvalidName -- uppercase for output visibility.
function PASS(string $format, ...$args): void {
  echo term_supports_color() ?
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
  echo term_supports_color() ?
    "\033[31m[FAIL] " . sprintf($format, ...$args) . "\033[0m\n" :
    sprintf('[FAIL] %s%s', sprintf($format, ...$args), PHP_EOL);
}

/**
 * Emit a single progress dot for a long-running task and flush it immediately.
 *
 * Flushing matters during a blocking transfer so the dots appear as the work
 * happens rather than all at once when the call returns.
 */
function progress_dot(): void {
  echo '.';
  flush();
}

/**
 * Sleep for a number of seconds, emitting a progress dot every second.
 *
 * Use inside a task() body so a fixed wait or a status-poll interval keeps the
 * task line ticking rather than appearing to hang.
 *
 * @param int $seconds
 *   The number of seconds to wait.
 */
function sleep_progress(int $seconds): void {
  for ($second = 0; $second < $seconds; $second++) {
    sleep(1);
    progress_dot();
  }
}

/**
 * Output a failure message.
 *
 * @param string $format
 *   Format string for sprintf().
 * @param bool|float|int|string|null ...$args
 *   Arguments for sprintf().
 */
// phpcs:ignore Drupal.NamingConventions.ValidFunctionName.InvalidName -- uppercase for output visibility.
function FAIL(string $format, ...$args): void {
  fail_no_exit($format, ...$args);
  quit(1);
}

/**
 * Check if terminal supports colors.
 */
function term_supports_color(): bool {
  return getenv('TERM') === 'dumb' || getenv('TERM') === FALSE ? FALSE : function_exists('posix_isatty') && @posix_isatty(STDOUT);
}

/**
 * Get the path to a command, or FALSE if the command does not exist.
 *
 * @param string $command
 *   Command name.
 *
 * @return string|false
 *   Path to the command, or FALSE if the command does not exist.
 */
function command_path(string $command): string|false {
  if (!preg_match('/^[A-Za-z0-9_\-]+(?: [A-Za-z0-9_\-]+)*$/', $command)) {
    return FALSE;
  }

  exec(sprintf('command -v %s 2>/dev/null', $command), $output, $code);

  return $code === 0 && !empty($output[0]) ? trim($output[0]) : FALSE;
}

/**
 * Require a command to be available, or fail.
 *
 * @param string $command
 *   Command name.
 */
function command_must_exist(string $command): void {
  if (!command_path($command)) {
    FAIL("Command '%s' is not available.", $command);
  }
}

/**
 * Whether the script runs on the host rather than inside the container.
 *
 * Docker availability implies the host. Set the RUN_ON_HOST environment
 * variable to '1' or '0' to override the detection.
 */
function run_on_host(): bool {
  $override = getenv('RUN_ON_HOST');

  if ($override !== FALSE && $override !== '') {
    return $override === '1';
  }

  return command_path('docker') !== FALSE;
}

/**
 * Create a directory for database dumps unless it already exists.
 *
 * @param string $dir
 *   The directory path to create.
 */
function prepare_db_dir(string $dir): void {
  if (is_dir($dir)) {
    return;
  }

  TASK('Creating directory for database dumps.', 'Created directory for database dumps.', function () use ($dir): void {
    if (!mkdir($dir, 0755, TRUE)) {
      throw new \RuntimeException(sprintf('Unable to create directory "%s".', $dir));
    }
  });
}

/**
 * Run a command via passthru, failing if exit code is non-zero.
 *
 * @param string $cmd
 *   Command to execute.
 * @param string $format
 *   (optional) Error message format string. If provided, calls fail() with
 *   this message on non-zero exit. If omitted, calls quit() with the exit
 *   code.
 * @param bool|float|int|string|null ...$args
 *   Arguments for sprintf() in the error message.
 */
function passthru_or_fail(string $cmd, string $format = '', ...$args): void {
  passthru($cmd, $exit_code);
  if ($exit_code !== 0) {
    if ($format !== '') {
      FAIL($format, ...$args);
    }
    quit($exit_code);
  }
}

/**
 * Run a drush command.
 *
 * By default, this function will call fail() on non-zero exit codes.
 * To suppress the automatic failure and handle the exit code yourself,
 * pass an initialized variable (e.g. `$exit_code = 0`) as the second
 * argument. The exit code will be written to that variable via the
 * by-reference parameter.
 *
 * Example (hard fail):
 *
 * @code
 *   drush('cr');
 * @endcode
 *
 * Example (soft fail):
 * @code
 *   $exit_code = 0;
 *   $output = drush('status', $exit_code);
 *   if ($exit_code !== 0) {
 *     note('Drush command returned non-zero exit code.');
 *   }
 * @endcode
 *
 * @param string $command
 *   The drush command to run (without 'drush' prefix).
 * @param int|null $exit_code
 *   (optional) Variable to capture the exit code. Pass an initialized
 *   variable (e.g. `$exit_code = 0`) to suppress automatic fail() on
 *   non-zero exit. When NULL (default), non-zero exits trigger fail().
 *
 * @param-out int $exit_code
 *
 * @return string
 *   The command output.
 */
function drush(string $command, ?int &$exit_code = NULL): string {
  $exit_code_provided = $exit_code !== NULL;
  $exit_code = 0;

  ob_start();
  passthru('./vendor/bin/drush -y ' . $command, $exit_code);
  $output = ob_get_clean();

  // Stream the captured output so callers that ignore the return value still
  // surface Drush's messages, while callers that inspect the return still get
  // the string.
  echo $output;

  if (!$exit_code_provided && $exit_code !== 0) {
    FAIL('Drush command failed: %s', $command);
  }

  return $output ?: '';
}

/**
 * Convert a project-root-relative path to a drush-compatible path.
 *
 * Drush resolves relative paths against the Drupal root, which sits one
 * directory below the project root, so a './'-prefixed project path gains one
 * directory level. Other paths are returned unchanged.
 *
 * @param string $path
 *   The path relative to the project root.
 *
 * @return string
 *   The path relative to the Drupal root.
 */
function drush_relative_path(string $path): string {
  return str_starts_with($path, './') ? '../' . substr($path, 2) : $path;
}

/**
 * Resolve the Lagoon CLI binary, installing it on demand.
 *
 * Prefers a 'lagoon' already available on PATH. Otherwise reuses a binary
 * previously downloaded to the cache directory, or downloads and verifies it
 * there once. This lets the same scripts run inside a hosting environment where
 * the CLI is not pre-installed, without re-downloading on every invocation.
 *
 * @return string
 *   Path to the Lagoon CLI binary.
 */
function lagoon_cli_resolve(): string {
  if (command_path('lagoon')) {
    NOTE('Using the Lagoon CLI found on PATH.');
    return 'lagoon';
  }

  $dir = (string) getenv_default('VORTEX_LAGOONCLI_PATH', '.artifacts/tmp');
  $version = getenv_default('VORTEX_LAGOONCLI_VERSION', 'v0.32.0');
  $bin = $dir . '/lagoon';

  if (is_executable($bin)) {
    NOTE('Reusing the Lagoon CLI previously downloaded to "%s".', $bin);
    return $bin;
  }

  if (!is_dir($dir)) {
    mkdir($dir, 0755, TRUE);
  }

  $platform = strtolower(php_uname('s'));
  $arch = str_replace(['x86_64', 'aarch64'], ['amd64', 'arm64'], php_uname('m'));
  $base = sprintf('https://github.com/uselagoon/lagoon-cli/releases/download/%s', $version);
  $asset = sprintf('lagoon-cli-%s-%s-%s', $version, $platform, $arch);

  TASK(sprintf('Downloading the Lagoon CLI "%s" to "%s".', $version, $bin), sprintf('Downloaded the Lagoon CLI "%s" to "%s".', $version, $bin), function () use ($base, $asset, $bin): void {
    $response = request($base . '/' . $asset, ['method' => 'GET', 'save_to' => $bin, 'timeout' => 120]);
    if (!$response['ok']) {
      @unlink($bin);
      throw new \RuntimeException(sprintf('Failed to download the Lagoon CLI from "%s": %s', $base . '/' . $asset, $response['error'] ?? 'Unknown error'));
    }

    lagoon_cli_verify_checksum($bin, $base, $asset);

    chmod($bin, 0755);
  });

  return $bin;
}

/**
 * Verify a downloaded Lagoon CLI binary against the published checksums.
 *
 * @param string $bin
 *   Path to the downloaded binary; removed on a verification failure.
 * @param string $base
 *   Base release download URL.
 * @param string $asset
 *   Asset file name to look up in the checksums file.
 */
function lagoon_cli_verify_checksum(string $bin, string $base, string $asset): void {
  $response = request($base . '/checksums.txt', ['method' => 'GET', 'timeout' => 30]);
  if (!$response['ok']) {
    @unlink($bin);
    FAIL('Failed to download the Lagoon CLI checksums from "%s".', $base . '/checksums.txt');
  }

  $expected = '';
  foreach (explode("\n", (string) $response['body']) as $line) {
    $parts = preg_split('/\s+/', trim($line)) ?: [];
    if (count($parts) === 2 && $parts[1] === $asset) {
      $expected = $parts[0];
      break;
    }
  }

  if ($expected === '' || !hash_equals($expected, (string) hash_file('sha256', $bin))) {
    @unlink($bin);
    FAIL('Lagoon CLI checksum verification failed for "%s".', $asset);
  }
}

/**
 * Path to an ephemeral Lagoon CLI config file scoped to a single script run.
 *
 * Registering the instance in a throwaway file, and pointing every CLI command
 * at it, keeps a developer's default '~/.lagoon.yml' untouched - an instance of
 * the same name there is neither read nor overwritten. The file name is
 * suffixed with the process ID so concurrent runs sharing the same cache
 * directory do not truncate each other's config.
 *
 * @return string
 *   Path to the config file; its parent directory is created if missing.
 */
function lagoon_config_file(): string {
  $dir = (string) getenv_default('VORTEX_LAGOONCLI_PATH', '.artifacts/tmp');

  if (!is_dir($dir)) {
    mkdir($dir, 0755, TRUE);
  }

  return $dir . '/lagoon-cli-' . getmypid() . '.yml';
}

/**
 * Register a Lagoon CLI instance into an isolated config file.
 *
 * @param string $bin
 *   The Lagoon CLI binary.
 * @param string $config_file
 *   The config file to write the instance into, kept separate from the default
 *   '~/.lagoon.yml'.
 * @param string $instance
 *   The Lagoon instance name.
 * @param string $graphql
 *   The Lagoon instance GraphQL endpoint.
 * @param string $hostname
 *   The Lagoon instance SSH hostname.
 * @param string $port
 *   The Lagoon instance SSH port.
 */
function lagoon_config(string $bin, string $config_file, string $instance, string $graphql, string $hostname, string $port): void {
  // Seed a minimal valid config: 'config add' panics on an empty (nil-map)
  // file, and starting each run from a clean instance list keeps it isolated.
  file_put_contents($config_file, "lagoons: {}\n");

  passthru_or_fail(sprintf('%s --config-file %s config add --force --lagoon %s --graphql %s --hostname %s --port %s', escapeshellarg($bin), escapeshellarg($config_file), escapeshellarg($instance), escapeshellarg($graphql), escapeshellarg($hostname), escapeshellarg($port)), 'Failed to add Lagoon instance configuration.');
}

/**
 * Print the Lagoon CLI version to make a run observable.
 *
 * @param string $bin
 *   The Lagoon CLI binary.
 * @param string $config_file
 *   The isolated config file to run against.
 */
function lagoon_print_version(string $bin, string $config_file): void {
  TASK('Checking Lagoon CLI version.', 'Checked Lagoon CLI version.', function () use ($bin, $config_file): void {
    passthru(sprintf('%s --config-file %s --version 2>&1', escapeshellarg($bin), escapeshellarg($config_file)));
  });
}

/**
 * Run a Lagoon CLI subcommand and capture its output.
 *
 * The isolated config file and common authentication flags (instance, project
 * and, on a host, the SSH key) are threaded from the context; command-specific
 * flags (environment, backup id, output format) are provided by the caller in
 * the subcommand.
 *
 * @param string $bin
 *   The Lagoon CLI binary.
 * @param string $subcommand
 *   The subcommand with its command-specific flags.
 * @param array{instance: string, project: string, config_file: string, ssh_key?: string} $ctx
 *   Execution context. The isolated config file is always applied; the SSH key
 *   flag is omitted when 'ssh_key' is empty or 'false' (e.g. inside the hosting
 *   environment where identity is implicit).
 * @param int|null $exit_code
 *   (optional) Variable to capture the exit code. Pass an initialised variable
 *   (e.g. `$exit_code = 0`) to suppress the automatic fail() on non-zero exit.
 *
 * @param-out int $exit_code
 *
 * @return string
 *   The captured command output.
 */
function lagoon_exec(string $bin, string $subcommand, array $ctx, ?int &$exit_code = NULL): string {
  $ssh_key = $ctx['ssh_key'] ?? '';
  $ssh_key_flag = (!empty($ssh_key) && $ssh_key !== 'false') ? ' --ssh-key ' . escapeshellarg($ssh_key) : '';

  $cmd = sprintf('%s --config-file %s --force --skip-update-check%s --lagoon %s --project %s %s 2>&1', escapeshellarg($bin), escapeshellarg($ctx['config_file']), $ssh_key_flag, escapeshellarg($ctx['instance']), escapeshellarg($ctx['project']), $subcommand);

  $exit_code_provided = $exit_code !== NULL;
  if (!$exit_code_provided) {
    $exit_code = 0;
  }

  ob_start();
  passthru($cmd, $exit_code);
  $output = ob_get_clean();

  if (!$exit_code_provided && $exit_code !== 0) {
    FAIL('Lagoon CLI command "%s" failed with exit code %s. Output: %s', $subcommand, $exit_code, $output);
  }

  return $output === FALSE ? '' : $output;
}

/**
 * Resolve the Acquia CLI binary, installing it on demand.
 *
 * Prefers an 'acli' already available on PATH. Otherwise reuses a phar
 * previously downloaded to the cache directory, or downloads it there once.
 * This lets the same scripts run where acli is not pre-installed, without
 * re-downloading on every invocation. The Acquia CLI ships as a
 * platform-independent PHP phar, so there is no per-architecture asset.
 *
 * @return string
 *   Path to the Acquia CLI binary.
 */
function acli_resolve(): string {
  if (command_path('acli')) {
    NOTE('Using the Acquia CLI found on PATH.');
    return 'acli';
  }

  $dir = (string) getenv_default('VORTEX_ACLI_PATH', '.artifacts/tmp');
  $version = getenv_default('VORTEX_ACLI_VERSION', '2.61.3');
  $bin = $dir . '/acli';

  if (is_executable($bin)) {
    NOTE('Reusing the Acquia CLI previously downloaded to "%s".', $bin);
    return $bin;
  }

  if (!is_dir($dir)) {
    mkdir($dir, 0755, TRUE);
  }

  $url = sprintf('https://github.com/acquia/cli/releases/download/%s/acli.phar', $version);

  TASK(sprintf('Downloading the Acquia CLI "%s" to "%s".', $version, $bin), sprintf('Downloaded the Acquia CLI "%s" to "%s".', $version, $bin), function () use ($url, $bin): void {
    $response = request($url, ['method' => 'GET', 'save_to' => $bin, 'timeout' => 120]);
    if (!$response['ok']) {
      @unlink($bin);
      throw new \RuntimeException(sprintf('Failed to download the Acquia CLI from "%s": %s', $url, $response['error'] ?? 'Unknown error'));
    }

    chmod($bin, 0755);
  });

  return $bin;
}

/**
 * Path to an ephemeral Acquia CLI home directory scoped to a single run.
 *
 * Pointing 'ACLI_HOME' at a throwaway directory keeps acli's credentials,
 * cached tokens and active-environment state out of a developer's global
 * '~/.acquia' configuration. The directory name is suffixed with the process
 * ID so concurrent runs sharing the cache directory do not clash.
 *
 * @return string
 *   Path to the home directory; any stale copy is cleared and it is recreated.
 */
function acli_home(): string {
  $dir = (string) getenv_default('VORTEX_ACLI_PATH', '.artifacts/tmp');
  $home = $dir . '/acli-home-' . getmypid();

  // A home left behind by a crashed run that reused this PID would hand acli
  // stale cached credentials; clear it so every run starts from a clean home.
  acli_home_remove($home);
  mkdir($home, 0755, TRUE);

  // The isolated home caches acli's token and state; remove it when the run
  // ends so no credentials linger on disk afterwards.
  register_shutdown_function(static function () use ($home): void {
    // @codeCoverageIgnoreStart
    acli_home_remove($home);
    // @codeCoverageIgnoreEnd
  });

  return $home;
}

/**
 * Recursively remove an isolated Acquia CLI home directory.
 *
 * @param string $home
 *   Path to the directory to remove; a no-op when it does not exist.
 */
function acli_home_remove(string $home): void {
  if (!is_dir($home)) {
    return;
  }

  $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($home, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
  foreach ($items as $item) {
    // @codeCoverageIgnoreStart
    if (!$item instanceof \SplFileInfo) {
      continue;
    }
    // @codeCoverageIgnoreEnd

    if ($item->isDir()) {
      rmdir($item->getPathname());
    }
    else {
      unlink($item->getPathname());
    }
  }

  rmdir($home);
}

/**
 * Run an Acquia CLI subcommand and capture its output.
 *
 * The isolated home directory and API credentials are threaded from the context
 * so acli authenticates headlessly without touching the global '~/.acquia'
 * configuration; command-specific arguments are provided by the caller in the
 * subcommand.
 *
 * @param string $bin
 *   The Acquia CLI binary.
 * @param string $subcommand
 *   The subcommand with its command-specific arguments.
 * @param array{home: string, key: string, secret: string} $ctx
 *   Execution context: the isolated ACLI_HOME and the API key and secret.
 * @param int|null $exit_code
 *   (optional) Variable to capture the exit code. Pass an initialised variable
 *   (e.g. `$exit_code = 0`) to suppress the automatic fail() on non-zero exit.
 *
 * @param-out int $exit_code
 *
 * @return string
 *   The captured command output.
 */
function acli_exec(string $bin, string $subcommand, array $ctx, ?int &$exit_code = NULL): string {
  // Pass credentials through the process environment rather than the command
  // line, so they are never exposed in the process list (e.g. `ps`).
  putenv('ACLI_HOME=' . $ctx['home']);
  putenv('ACLI_KEY=' . $ctx['key']);
  putenv('ACLI_SECRET=' . $ctx['secret']);
  putenv('ACLI_NO_TELEMETRY=1');

  $cmd = sprintf('%s %s --no-interaction 2>&1', escapeshellarg($bin), $subcommand);

  $exit_code_provided = $exit_code !== NULL;
  if (!$exit_code_provided) {
    $exit_code = 0;
  }

  ob_start();
  passthru($cmd, $exit_code);
  $output = ob_get_clean();

  if (!$exit_code_provided && $exit_code !== 0) {
    FAIL('Acquia CLI command "%s" failed with exit code %s. Output: %s', $subcommand, $exit_code, $output);
  }

  return $output === FALSE ? '' : $output;
}

/**
 * Retrieve an Acquia Cloud API access token.
 *
 * @param string $key
 *   The Acquia Cloud API key.
 * @param string $secret
 *   The Acquia Cloud API secret.
 *
 * @return string
 *   The API access token.
 */
function acquia_api_get_token(string $key, string $secret): string {
  $response = request_post('https://accounts.acquia.com/api/auth/oauth/token', http_build_query([
    'client_id' => $key,
    'client_secret' => $secret,
    'grant_type' => 'client_credentials',
  ]), ['Content-Type: application/x-www-form-urlencoded']);

  if (!$response['ok']) {
    FAIL('Unable to retrieve a token.');
  }

  $data = json_decode((string) $response['body'], TRUE);
  $token = is_array($data) && is_string($data['access_token'] ?? NULL) ? $data['access_token'] : '';

  if ($token === '') {
    FAIL('Unable to retrieve a token.');
  }

  return $token;
}

/**
 * Build Acquia Cloud API request headers.
 *
 * @param string $token
 *   The API access token.
 *
 * @return array<int, string>
 *   The request headers.
 */
function acquia_api_headers(string $token): array {
  return [
    'Accept: application/json, version=2',
    'Authorization: Bearer ' . $token,
  ];
}

/**
 * Extract the last item from an Acquia Cloud API embedded collection response.
 *
 * @param mixed $data
 *   The decoded JSON response body.
 *
 * @return array<mixed>|null
 *   The last embedded item, or NULL when none is present.
 */
function acquia_api_last_embedded_item(mixed $data): ?array {
  $items = (is_array($data) && isset($data['_embedded']) && is_array($data['_embedded']) && isset($data['_embedded']['items']) && is_array($data['_embedded']['items'])) ? $data['_embedded']['items'] : [];
  $last = end($items);

  return is_array($last) ? $last : NULL;
}

/**
 * Retrieve an Acquia Cloud application UUID by application name.
 *
 * @param string $token
 *   The API access token.
 * @param string $app_name
 *   The application name.
 *
 * @return string
 *   The application UUID.
 */
function acquia_api_get_app_uuid(string $token, string $app_name): string {
  $url = 'https://cloud.acquia.com/api/applications?filter=name%3D' . rawurlencode($app_name);
  $response = request_get($url, acquia_api_headers($token));

  if (!$response['ok']) {
    FAIL('Unable to retrieve an application UUID.');
  }

  $last = acquia_api_last_embedded_item(json_decode((string) $response['body'], TRUE));
  $uuid = is_string($last['uuid'] ?? NULL) ? $last['uuid'] : '';

  if ($uuid === '') {
    FAIL('Unable to retrieve an application UUID.');
  }

  return $uuid;
}

/**
 * Retrieve an Acquia Cloud environment ID by environment name.
 *
 * @param string $token
 *   The API access token.
 * @param string $app_uuid
 *   The application UUID.
 * @param string $env_name
 *   The environment name.
 *
 * @return string
 *   The environment ID.
 */
function acquia_api_get_env_id(string $token, string $app_uuid, string $env_name): string {
  $url = sprintf('https://cloud.acquia.com/api/applications/%s/environments?filter=name%%3D%s', $app_uuid, rawurlencode($env_name));
  $response = request_get($url, acquia_api_headers($token));

  if (!$response['ok']) {
    FAIL('Unable to retrieve environment ID for %s.', $env_name);
  }

  $last = acquia_api_last_embedded_item(json_decode((string) $response['body'], TRUE));

  $env_id = '';
  if ($last !== NULL && isset($last['id']) && (is_string($last['id']) || is_int($last['id']))) {
    $env_id = (string) $last['id'];
  }

  if ($env_id === '') {
    FAIL('Unable to retrieve environment ID for %s.', $env_name);
  }

  return $env_id;
}

/**
 * Poll an Acquia Cloud API notification until it completes.
 *
 * The access token expires after five minutes, so a long poll can outlive it;
 * the token is refreshed on a 401 response and the check is retried.
 *
 * @param string $key
 *   The Acquia Cloud API key.
 * @param string $secret
 *   The Acquia Cloud API secret.
 * @param string $notification_url
 *   The notification URL returned by the operation request.
 * @param int $retries
 *   Number of status checks before giving up.
 * @param int $interval
 *   Seconds to wait between status checks.
 * @param string $token
 *   (optional) An existing API access token to start with. A fresh token is
 *   retrieved when not provided.
 *
 * @return bool
 *   TRUE when the operation completed within the retries.
 */
function acquia_api_poll_notification(string $key, string $secret, string $notification_url, int $retries, int $interval, string $token = ''): bool {
  $token = $token === '' ? acquia_api_get_token($key, $secret) : $token;

  for ($i = 1; $i <= $retries; $i++) {
    sleep_progress($interval);

    $response = request_get($notification_url, acquia_api_headers($token));

    if (!$response['ok'] && ($response['status'] ?? 0) === 401) {
      $token = acquia_api_get_token($key, $secret);
      $response = request_get($notification_url, acquia_api_headers($token));
    }

    if ($response['ok']) {
      $data = json_decode((string) $response['body'], TRUE);

      if (is_array($data) && ($data['status'] ?? '') === 'completed') {
        return TRUE;
      }
    }
  }

  return FALSE;
}

/**
 * Compute an HMAC-SHA256 digest with either a raw or a hex-encoded key.
 *
 * @param string $key
 *   The signing key.
 * @param string $data
 *   The data to sign.
 * @param bool $hex_key
 *   Whether the key is hex-encoded and should be converted to binary first.
 *
 * @return string
 *   The hex-encoded digest.
 */
function hmac_sha256(string $key, string $data, bool $hex_key = FALSE): string {
  if ($hex_key) {
    $key = (string) hex2bin($key);
  }

  return hash_hmac('sha256', $data, $key);
}

/**
 * Derive an AWS Signature Version 4 signature.
 *
 * @param string $secret_key
 *   The AWS secret key.
 * @param string $date_short
 *   The request date in 'Ymd' format.
 * @param string $region
 *   The AWS region.
 * @param string $service
 *   The AWS service, e.g. 's3'.
 * @param string $string_to_sign
 *   The string to sign.
 *
 * @return string
 *   The hex-encoded signature.
 */
function create_aws_signature(string $secret_key, string $date_short, string $region, string $service, string $string_to_sign): string {
  $date_key = hmac_sha256('AWS4' . $secret_key, $date_short);
  $region_key = hmac_sha256($date_key, $region, TRUE);
  $service_key = hmac_sha256($region_key, $service, TRUE);
  $signing_key = hmac_sha256($service_key, 'aws4_request', TRUE);

  return hmac_sha256($signing_key, $string_to_sign, TRUE);
}

/**
 * Build signed headers for an AWS S3 request (Signature Version 4).
 *
 * Produces the canonical request, signs it and returns the full set of HTTP
 * headers - including the 'Authorization' header - to pass to request().
 *
 * @param string $method
 *   The HTTP method, e.g. 'GET' or 'PUT'.
 * @param string $bucket
 *   The S3 bucket name.
 * @param string $region
 *   The S3 region.
 * @param string $uri
 *   The request path starting with '/'.
 * @param string $payload_hash
 *   SHA256 hash of the request payload (hash of an empty string for GET).
 * @param string $access_key
 *   The AWS access key.
 * @param string $secret_key
 *   The AWS secret key.
 * @param array<string, string> $extra_headers
 *   (optional) Additional headers to sign, keyed by lowercase header name,
 *   e.g. ['x-amz-storage-class' => 'STANDARD'].
 *
 * @return array<int, string>
 *   The HTTP header lines, including the computed 'Authorization' header.
 */
function aws_s3_signed_headers(string $method, string $bucket, string $region, string $uri, string $payload_hash, string $access_key, string $secret_key, array $extra_headers = []): array {
  $service = 's3';
  $host = sprintf('%s.%s.%s.amazonaws.com', $bucket, $service, $region);
  $date_short = gmdate('Ymd');
  $date_long = gmdate('Ymd\THis\Z');

  $headers = [
    'content-type' => 'application/octet-stream',
    'host' => $host,
    'x-amz-content-sha256' => $payload_hash,
    'x-amz-date' => $date_long,
  ] + $extra_headers;
  ksort($headers);

  $canonical_headers = [];
  foreach ($headers as $name => $value) {
    $canonical_headers[] = $name . ':' . $value;
  }

  $signed_header_names = implode(';', array_keys($headers));
  $canonical_request = sprintf("%s\n%s\n\n%s\n\n%s\n%s", $method, $uri, implode("\n", $canonical_headers), $signed_header_names, $payload_hash);
  $string_to_sign = sprintf("%s\n%s\n%s/%s/%s/aws4_request\n%s", 'AWS4-HMAC-SHA256', $date_long, $date_short, $region, $service, hash('sha256', $canonical_request));
  $signature = create_aws_signature($secret_key, $date_short, $region, $service, $string_to_sign);

  $lines = [];
  foreach ($headers as $name => $value) {
    if ($name === 'host') {
      continue;
    }

    $lines[] = $name . ': ' . $value;
  }

  $lines[] = sprintf('Authorization: AWS4-HMAC-SHA256 Credential=%s/%s/%s/%s/aws4_request, SignedHeaders=%s, Signature=%s', $access_key, $date_short, $region, $service, $signed_header_names, $signature);

  return $lines;
}

/**
 * Recursively copy a directory.
 *
 * @param string $src
 *   Source directory path.
 * @param string $dst
 *   Destination directory path.
 */
function copy_dir(string $src, string $dst): void {
  if (!is_dir($dst)) {
    mkdir($dst, 0755, TRUE);
  }

  $iterator = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
    \RecursiveIteratorIterator::SELF_FIRST
  );

  /** @var \RecursiveDirectoryIterator $item */
  foreach ($iterator as $item) {
    $target = $dst . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
    if ($item->isDir()) {
      if (!is_dir($target)) {
        mkdir($target, 0755, TRUE);
      }
    }
    else {
      copy($item->getPathname(), $target);
    }
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
    $escaped = json_encode($value, JSON_UNESCAPED_SLASHES);
    if ($escaped === FALSE) {
      // @codeCoverageIgnoreStart
      continue;
      // @codeCoverageIgnoreEnd
    }
    $replace[] = substr($escaped, 1, -1);
    $search[] = sprintf('%%%s%%', $token);
  }

  return str_replace($search, $replace, $template);
}

/**
 * Quit when a branch-filtered notification channel does not apply.
 *
 * Notification channels are gated by an optional comma-separated branch
 * allowlist. An empty allowlist applies no filtering. When the current branch
 * is not listed, the skip is reported and the script exits successfully so
 * the notify router can carry on with other channels.
 *
 * @param string $branches
 *   Comma-separated branch allowlist.
 * @param string $label
 *   Channel label for the skip message, e.g. 'email' or 'Slack'.
 * @param string|null $branch
 *   (optional) The branch to check; defaults to VORTEX_NOTIFY_BRANCH.
 */
function notify_skip_unlisted_branch(string $branches, string $label, ?string $branch = NULL): void {
  if ($branches === '') {
    return;
  }

  $branch = $branch ?? (string) (getenv('VORTEX_NOTIFY_BRANCH') ?: '');
  $branch_list = array_map(trim(...), explode(',', $branches));

  if (!in_array($branch, $branch_list, TRUE)) {
    PASS("Skipped %s notification for branch '%s'.", $label, $branch);
    quit();
  }
}

/**
 * Quit when a post-deployment-only channel receives a pre_deployment event.
 *
 * @param string $event
 *   The notification event type.
 * @param string $label
 *   Channel label for the skip message, e.g. 'email' or 'Slack'.
 */
function notify_skip_pre_deployment(string $event, string $label): void {
  if ($event === 'pre_deployment') {
    PASS('Skipped %s notification for pre_deployment event.', $label);
    quit();
  }
}

/**
 * Convert a string map to an associative array.
 *
 * @param string $map
 *   String map in the format "key1=value1,key2=value2".
 * @param string $separator
 *   Separator between key/value pairs (default: ',').
 * @param string $key_value_separator
 *   Separator between key and value (default: '=').
 *
 * @return array<string,string>
 *   Associative array of key/value pairs.
 */
function string_map_to_array(string $map, string $separator = ',', string $key_value_separator = '='): array {
  $array = [];

  // Return empty array for empty map.
  if (empty(trim($map))) {
    return $array;
  }

  $separator = empty($separator) ? ',' : $separator;
  $key_value_separator = empty($key_value_separator) ? '=' : $key_value_separator;

  $pairs = array_map('trim', explode($separator, $map));

  foreach ($pairs as $pair) {
    $parts = explode($key_value_separator, $pair, 2);
    if (count($parts) !== 2) {
      FAIL('invalid key/value pair "%s" provided.', $pair);
    }
    $array[trim($parts[0])] = trim($parts[1]);
  }

  return $array;
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
 * @param array{method?: string, headers?: array<int, string>, body?: mixed, timeout?: int, save_to?: string, upload_file?: string, auth?: string, follow_redirects?: bool} $options
 *   Array of options:
 *   - method: HTTP method (GET, POST, PUT, etc.)
 *   - headers: Array of HTTP headers
 *   - body: Request body
 *   - timeout: Request timeout in seconds
 *   - save_to: Path to save response body to file
 *   - upload_file: Path to file to upload (sets CURLOPT_UPLOAD)
 *   - auth: 'user:pass' for CURLOPT_USERPWD authentication
 *   - follow_redirects: Whether to follow HTTP redirects (default: TRUE).
 *     When FALSE, the redirect target is exposed via info.redirect_url.
 *
 * @return array{ok: bool, status: int, body: string|false, error: string|null, info: array<string, mixed>}
 *   Array with keys:
 *   - ok: TRUE if request was successful (HTTP < 400), FALSE otherwise
 *   - status: HTTP status code
 *   - body: Response body (empty string when save_to is used)
 *   - error: Error message if any
 *   - info: Request info array
 */
function request(string $url, array $options = []): array {
  // @codeCoverageIgnoreStart
  if (!function_exists('curl_init')) {
    FAIL('curl extension is not available.');
  }
  // @codeCoverageIgnoreEnd
  $ch = curl_init($url);

  // @codeCoverageIgnoreStart
  if ($ch === FALSE) {
    return [
      'ok' => FALSE,
      'status' => 0,
      'body' => '',
      'error' => sprintf('Failed to initialize curl for URL: %s', $url),
      'info' => [],
    ];
  }
  // @codeCoverageIgnoreEnd
  $upload_fh = NULL;
  $save_fh = NULL;

  try {
    /** @var array<int, mixed> $opts */
    $opts = [
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_FOLLOWLOCATION => $options['follow_redirects'] ?? TRUE,
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

    if (isset($options['auth'])) {
      $opts[CURLOPT_USERPWD] = $options['auth'];
    }

    if (isset($options['upload_file'])) {
      $upload_fh = fopen($options['upload_file'], 'r');
      if ($upload_fh === FALSE) {
        unset($ch);
        return [
          'ok' => FALSE,
          'status' => 0,
          'body' => '',
          'error' => sprintf('Failed to open upload file: %s', $options['upload_file']),
          'info' => [],
        ];
      }
      $opts[CURLOPT_UPLOAD] = TRUE;
      $opts[CURLOPT_INFILE] = $upload_fh;
      $opts[CURLOPT_INFILESIZE] = filesize($options['upload_file']);
    }

    // Stream response directly to file to avoid buffering large responses.
    if (isset($options['save_to'])) {
      $save_fh = fopen($options['save_to'], 'w');
      if ($save_fh === FALSE) {
        if ($upload_fh !== NULL && is_resource($upload_fh)) {
          fclose($upload_fh);
        }
        unset($ch);
        return [
          'ok' => FALSE,
          'status' => 0,
          'body' => '',
          'error' => sprintf('Failed to open save file: %s', $options['save_to']),
          'info' => [],
        ];
      }
      $opts[CURLOPT_FILE] = $save_fh;
      unset($opts[CURLOPT_RETURNTRANSFER]);
    }

    // Report transfer progress to the caller's callback about once a second.
    // The callback owns any output; request() stays output-agnostic.
    if (isset($options['on_progress']) && is_callable($options['on_progress'])) {
      $on_progress = $options['on_progress'];
      $opts[CURLOPT_NOPROGRESS] = FALSE;
      $opts[CURLOPT_XFERINFOFUNCTION] = static function (mixed $ch, int $dltotal, int $dlnow, int $ultotal, int $ulnow) use ($on_progress): int {
        // @codeCoverageIgnoreStart
        static $last = 0;
        $now = time();

        if ($dlnow > 0 && $now !== $last) {
          $on_progress();
          $last = $now;
        }

        return 0;
        // @codeCoverageIgnoreEnd
      };
    }

    curl_setopt_array($ch, $opts);

    $result = curl_exec($ch);
    $error = curl_errno($ch) ? curl_error($ch) : NULL;
    $info = curl_getinfo($ch);

    // Handle curl_getinfo failure.
    if ($info === FALSE) {
      // @codeCoverageIgnoreStart
      $info = ['http_code' => 0];
      // @codeCoverageIgnoreEnd
    }

    // With CURLOPT_FILE, curl_exec returns bool — normalize to string|false.
    // With CURLOPT_RETURNTRANSFER, curl_exec returns string|false directly.
    /** @var string|false $body */
    $body = $save_fh !== NULL ? '' : $result;

    return [
      'ok' => !$error && ($info['http_code'] < 400),
      'status' => $info['http_code'],
      'body' => $body,
      'error' => $error,
      'info' => $info,
    ];
  }
  finally {
    if ($upload_fh !== NULL && is_resource($upload_fh)) {
      fclose($upload_fh);
    }

    if ($save_fh !== NULL && is_resource($save_fh)) {
      fclose($save_fh);
    }

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
