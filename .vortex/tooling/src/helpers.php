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
  fail('Missing required value for %s', $var_list);

  // Never reached, but satisfies return type.
  // @codeCoverageIgnoreStart
  return '';
  // @codeCoverageIgnoreEnd
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
 * Announce a task, or run its body and report the outcome.
 *
 * With only a message, announces the task - a caller then follows with its own
 * pass()/fail(). With a done message and a body, it runs the body under a live
 * [TASK] line (the body may emit progress dots via progress_dot()/
 * sleep_progress() while it works), then reports [ OK ] with the done message.
 * The body signals failure by throwing: the thrown message is reported as
 * [FAIL] and the script exits with an error. This keeps each task's intent,
 * work and outcome in one place and guarantees every task ends with a status.
 *
 * @param string $doing
 *   The present-tense task message, e.g. 'Downloading the backup.'.
 * @param string|\Closure|null $done
 *   The success message reported when a body completes. A closure receives the
 *   body's return value and produces the message, for outcomes known only once
 *   the work is done.
 * @param callable|null $body
 *   The work to perform; throw to fail the task with the thrown message.
 *
 * @return mixed
 *   Whatever the body returns, or NULL when announcing only.
 */
function task(string $doing, string|\Closure|null $done = NULL, ?callable $body = NULL): mixed {
  $color = term_supports_color();

  if ($body === NULL) {
    echo $color ? "\033[34m[TASK] " . $doing . "\033[0m" . PHP_EOL : '[TASK] ' . $doing . PHP_EOL;

    return NULL;
  }

  echo $color ? "\033[34m[TASK] " . $doing : '[TASK] ' . $doing;

  try {
    $result = $body();
    echo ($color ? "\033[0m" : '') . PHP_EOL;
    pass('%s', $done instanceof \Closure ? (string) $done($result) : (string) $done);

    return $result;
  }
  catch (\Throwable $e) {
    echo ($color ? "\033[0m" : '') . PHP_EOL;
    fail('%s', $e->getMessage());
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
function info(string $format, ...$args): void {
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
function pass(string $format, ...$args): void {
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
 * Run an operation under a task line that stays open for progress dots.
 *
 * Prints the task line without a trailing newline, runs the operation - which
 * emits its own progress dots while it works (see progress_dot()) - then closes
 * the line. This keeps long-running steps such as downloads and status polling
 * visibly alive instead of appearing to hang.
 *
 * @param string $message
 *   The already-formatted task message.
 * @param callable $operation
 *   The work to run; its return value is passed through unchanged.
 *
 * @return mixed
 *   Whatever $operation returns.
 */
function task_progress(string $message, callable $operation): mixed {
  $color = term_supports_color();
  // Leave the colour open so the progress dots inherit the task colour; it is
  // closed together with the trailing newline once the operation returns.
  echo $color ? "\033[34m[TASK] " . $message : '[TASK] ' . $message;

  try {
    return $operation();
  }
  finally {
    echo ($color ? "\033[0m" : '') . PHP_EOL;
  }
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
 * Use inside a task_progress() operation so a fixed wait or a status-poll
 * interval keeps the task line ticking rather than appearing to hang.
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
function fail(string $format, ...$args): void {
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
    fail(sprintf("Command '%s' is not available.", $command));
  }
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
      fail($format, ...$args);
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
    fail('Drush command failed: %s', $command);
  }

  return $output ?: '';
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
    note('Using the Lagoon CLI found on PATH.');
    return 'lagoon';
  }

  $dir = (string) getenv_default('VORTEX_LAGOONCLI_PATH', '.artifacts/tmp');
  $version = getenv_default('VORTEX_LAGOONCLI_VERSION', 'v0.32.0');
  $bin = $dir . '/lagoon';

  if (is_executable($bin)) {
    note('Reusing the Lagoon CLI previously downloaded to "%s".', $bin);
    return $bin;
  }

  if (!is_dir($dir)) {
    mkdir($dir, 0755, TRUE);
  }

  $platform = strtolower(php_uname('s'));
  $arch = str_replace(['x86_64', 'aarch64'], ['amd64', 'arm64'], php_uname('m'));
  $base = sprintf('https://github.com/uselagoon/lagoon-cli/releases/download/%s', $version);
  $asset = sprintf('lagoon-cli-%s-%s-%s', $version, $platform, $arch);

  task(sprintf('Downloading the Lagoon CLI "%s" to "%s".', $version, $bin));
  $response = request($base . '/' . $asset, ['method' => 'GET', 'save_to' => $bin, 'timeout' => 120]);
  if (!$response['ok']) {
    @unlink($bin);
    fail('Failed to download the Lagoon CLI from "%s": %s', $base . '/' . $asset, $response['error'] ?? 'Unknown error');
  }

  lagoon_cli_verify_checksum($bin, $base, $asset);

  chmod($bin, 0755);

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
    fail('Failed to download the Lagoon CLI checksums from "%s".', $base . '/checksums.txt');
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
    fail('Lagoon CLI checksum verification failed for "%s".', $asset);
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
  task('Checking Lagoon CLI version.');
  passthru(sprintf('%s --config-file %s --version 2>&1', escapeshellarg($bin), escapeshellarg($config_file)));
  pass('Checked Lagoon CLI version.');
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
    fail('Lagoon CLI command "%s" failed with exit code %s. Output: %s', $subcommand, $exit_code, $output);
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
    note('Using the Acquia CLI found on PATH.');
    return 'acli';
  }

  $dir = (string) getenv_default('VORTEX_ACLI_PATH', '.artifacts/tmp');
  $version = getenv_default('VORTEX_ACLI_VERSION', '2.61.3');
  $bin = $dir . '/acli';

  if (is_executable($bin)) {
    note('Reusing the Acquia CLI previously downloaded to "%s".', $bin);
    return $bin;
  }

  if (!is_dir($dir)) {
    mkdir($dir, 0755, TRUE);
  }

  $url = sprintf('https://github.com/acquia/cli/releases/download/%s/acli.phar', $version);

  task(sprintf('Downloading the Acquia CLI "%s" to "%s".', $version, $bin));
  $response = request($url, ['method' => 'GET', 'save_to' => $bin, 'timeout' => 120]);
  if (!$response['ok']) {
    @unlink($bin);
    fail('Failed to download the Acquia CLI from "%s": %s', $url, $response['error'] ?? 'Unknown error');
  }

  chmod($bin, 0755);

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
 *   Path to the home directory; it is created if missing.
 */
function acli_home(): string {
  $dir = (string) getenv_default('VORTEX_ACLI_PATH', '.artifacts/tmp');
  $home = $dir . '/acli-home-' . getmypid();

  if (!is_dir($home)) {
    mkdir($home, 0755, TRUE);
  }

  return $home;
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
  $env = sprintf('ACLI_HOME=%s ACLI_KEY=%s ACLI_SECRET=%s ACLI_NO_TELEMETRY=1', escapeshellarg($ctx['home']), escapeshellarg($ctx['key']), escapeshellarg($ctx['secret']));
  $cmd = sprintf('%s %s %s --no-interaction 2>&1', $env, escapeshellarg($bin), $subcommand);

  $exit_code_provided = $exit_code !== NULL;
  if (!$exit_code_provided) {
    $exit_code = 0;
  }

  ob_start();
  passthru($cmd, $exit_code);
  $output = ob_get_clean();

  if (!$exit_code_provided && $exit_code !== 0) {
    fail('Acquia CLI command "%s" failed with exit code %s. Output: %s', $subcommand, $exit_code, $output);
  }

  return $output === FALSE ? '' : $output;
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
      fail(sprintf('invalid key/value pair "%s" provided.', $pair));
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
    fail('curl extension is not available.');
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
