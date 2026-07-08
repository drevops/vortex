#!/usr/bin/env php
<?php

/**
 * @file
 * Manual runner for the Acquia database download script.
 *
 * Exercises 'vortex-fetch-db-acquia' end to end against a real Acquia Cloud
 * environment - acli resolution, the isolated ACLI_HOME auth and the backup
 * download - without a full project checkout. Every connection detail is read
 * from the environment; nothing is hardcoded.
 *
 * Usage:
 *   VORTEX_FETCH_DB_ACQUIA_KEY=... \
 *   VORTEX_FETCH_DB_ACQUIA_SECRET=... \
 *   VORTEX_FETCH_DB_ACQUIA_APP_NAME="My App" \
 *   VORTEX_FETCH_DB_ENVIRONMENT=prod \
 *   VORTEX_FETCH_DB_ACQUIA_DB_NAME=my_db \
 *   ./playground/fetch-db-acquia.php [--fresh]
 *
 * Pass '--fresh' (or set VORTEX_FETCH_DB_FRESH=1) to trigger a new backup and
 * wait for it instead of downloading the latest existing one.
 *
 * Optional overrides (defaults in brackets):
 *   VORTEX_FETCH_DB_ACQUIA_DB_DIR   [.artifacts/tmp/playground-acquia]
 *   VORTEX_FETCH_DB_ACQUIA_DB_FILE  [db.sql]
 *   VORTEX_ACLI_PATH                [.artifacts/tmp/playground-acquia]
 *                                   acli phar and isolated ACLI_HOME.
 */

declare(strict_types=1);

namespace DrevOps\VortexTooling;

require_once __DIR__ . '/../src/helpers.php';

echo "=== Acquia database download runner ===\n\n";

$required = [
  'VORTEX_FETCH_DB_ACQUIA_KEY',
  'VORTEX_FETCH_DB_ACQUIA_SECRET',
  'VORTEX_FETCH_DB_ACQUIA_APP_NAME',
  'VORTEX_FETCH_DB_ENVIRONMENT',
  'VORTEX_FETCH_DB_ACQUIA_DB_NAME',
];

$missing = array_filter($required, fn(string $name): bool => getenv($name) === FALSE || getenv($name) === '');
if ($missing !== []) {
  // Nothing to download without credentials and a target, so an unconfigured
  // run is a no-op rather than a failure.
  echo "Skipping: set " . implode(', ', $missing) . " to run.\n";
  echo "See the file header for the full usage example.\n";
  exit(0);
}

// Keep all scratch under the gitignored '.artifacts/tmp' at the repo root.
$scratch = dirname(__DIR__, 3) . '/.artifacts/tmp/playground-acquia';

// Force a new backup with the '--fresh' flag or VORTEX_FETCH_DB_FRESH=1.
$fresh = in_array('--fresh', (array) ($_SERVER['argv'] ?? []), TRUE) || getenv('VORTEX_FETCH_DB_FRESH') === '1';

// Playground-scoped defaults; every value is overridable via the environment.
$config = [
  'VORTEX_FETCH_DB_ACQUIA_DB_DIR' => getenv('VORTEX_FETCH_DB_ACQUIA_DB_DIR') ?: $scratch,
  'VORTEX_FETCH_DB_ACQUIA_DB_FILE' => getenv('VORTEX_FETCH_DB_ACQUIA_DB_FILE') ?: 'db.sql',
  'VORTEX_ACLI_PATH' => getenv('VORTEX_ACLI_PATH') ?: $scratch,
  'VORTEX_FETCH_DB_FRESH' => $fresh ? '1' : '',
];

foreach ($config as $name => $value) {
  putenv($name . '=' . $value);
}

echo "Configuration:\n";
echo "  Application:  " . getenv('VORTEX_FETCH_DB_ACQUIA_APP_NAME') . "\n";
echo "  Environment:  " . getenv('VORTEX_FETCH_DB_ENVIRONMENT') . "\n";
echo "  Database:     " . getenv('VORTEX_FETCH_DB_ACQUIA_DB_NAME') . "\n";
echo "  Download dir: " . $config['VORTEX_FETCH_DB_ACQUIA_DB_DIR'] . "\n";
echo "  acli cache:   " . $config['VORTEX_ACLI_PATH'] . "\n";
echo "  Fresh backup: " . ($fresh ? 'yes' : 'no (use the latest existing backup)') . "\n\n";

$dest = $config['VORTEX_FETCH_DB_ACQUIA_DB_DIR'] . '/' . $config['VORTEX_FETCH_DB_ACQUIA_DB_FILE'];
if (is_file($dest)) {
  unlink($dest);
}

echo "Running vortex-fetch-db-acquia...\n\n";
// Inherit the real stdio streams so the script sees a TTY and keeps its colour
// output; a piped subprocess (passthru) would disable colours.
$descriptors = [0 => STDIN, 1 => STDOUT, 2 => STDERR];
$process = proc_open('php ' . escapeshellarg(__DIR__ . '/../src/vortex-fetch-db-acquia'), $descriptors, $pipes);
$exit_code = is_resource($process) ? proc_close($process) : 1;

echo "\n";
echo "Exit code: " . $exit_code . "\n";

if (is_file($dest)) {
  $size = (int) filesize($dest);
  $handle = fopen($dest, 'r');
  $first_line = $handle !== FALSE ? (fgets($handle) ?: '') : '';
  if ($handle !== FALSE) {
    fclose($handle);
  }
  echo "Downloaded:   " . $dest . " (" . number_format($size) . " bytes)\n";
  echo "First line:   " . trim($first_line) . "\n";
  echo "\nResult: " . ($exit_code === 0 ? "\xE2\x9C\x93 SUCCESS" : "\xE2\x9C\x97 FAILED (non-zero exit)") . "\n";
}
else {
  echo "\nResult: \xE2\x9C\x97 FAILED (no dump written to " . $dest . ")\n";
}
