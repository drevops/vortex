#!/usr/bin/env php
<?php

/**
 * @file
 * Manual runner for the Lagoon database download script.
 *
 * Exercises 'vortex-fetch-db-lagoon' end to end against a real Lagoon
 * environment - CLI resolution, the isolated per-run config file and the
 * asynchronous backup download - without a full project checkout. Every
 * connection detail is read from the environment; nothing is hardcoded.
 *
 * Usage:
 *   LAGOON_PROJECT=drevops-website ./playground/fetch-db-lagoon.php
 *   LAGOON_PROJECT=drevops-website ./playground/fetch-db-lagoon.php --fresh
 *
 * Pass '--fresh' (or set VORTEX_FETCH_DB_FRESH=1) to force a new dump instead
 * of reusing a recent one.
 *
 * Optional overrides (defaults in brackets):
 *   VORTEX_FETCH_DB_ENVIRONMENT      [main]      Source environment.
 *   VORTEX_FETCH_DB_LAGOON_INSTANCE  [amazeeio]  Lagoon instance name.
 *   VORTEX_FETCH_DB_SSH_FILE         [false]     'false' uses the ambient SSH
 *                                                identity (e.g. your running
 *                                                ssh-agent); set a key path to
 *                                                authenticate through
 *                                                vortex-setup-ssh instead.
 *   VORTEX_FETCH_DB_LAGOON_DB_DIR    [.artifacts/tmp/playground-lagoon]
 *   VORTEX_FETCH_DB_LAGOON_DB_FILE   [db.sql]
 *   VORTEX_LAGOONCLI_PATH            [.artifacts/tmp/playground-lagoon]
 *                                                CLI binary and config cache.
 */

declare(strict_types=1);

namespace DrevOps\VortexTooling;

require_once __DIR__ . '/../src/helpers.php';

echo "=== Lagoon database download runner ===\n\n";

$project = getenv('VORTEX_FETCH_DB_LAGOON_PROJECT') ?: getenv('LAGOON_PROJECT');
if ($project === FALSE || $project === '') {
  // Nothing to download without a target project, so an unconfigured run (no
  // env vars set) is a no-op rather than a failure.
  echo "Skipping: set LAGOON_PROJECT (or VORTEX_FETCH_DB_LAGOON_PROJECT) to a Lagoon\n";
  echo "project to run, e.g. LAGOON_PROJECT=drevops-website ./playground/fetch-db-lagoon.php\n";
  exit(0);
}

// Keep all scratch under the gitignored '.artifacts/tmp' at the repo root.
$scratch = dirname(__DIR__, 3) . '/.artifacts/tmp/playground-lagoon';

// Force a new dump with the '--fresh' flag or VORTEX_FETCH_DB_FRESH=1.
$fresh = in_array('--fresh', (array) ($_SERVER['argv'] ?? []), TRUE) || getenv('VORTEX_FETCH_DB_FRESH') === '1';

// Playground-scoped defaults; every value is overridable via the environment.
$config = [
  'VORTEX_FETCH_DB_LAGOON_PROJECT' => (string) $project,
  'VORTEX_FETCH_DB_ENVIRONMENT' => getenv('VORTEX_FETCH_DB_ENVIRONMENT') ?: 'main',
  'VORTEX_FETCH_DB_LAGOON_INSTANCE' => getenv('VORTEX_FETCH_DB_LAGOON_INSTANCE') ?: 'amazeeio',
  'VORTEX_FETCH_DB_SSH_FILE' => getenv('VORTEX_FETCH_DB_SSH_FILE') ?: 'false',
  'VORTEX_FETCH_DB_LAGOON_DB_DIR' => getenv('VORTEX_FETCH_DB_LAGOON_DB_DIR') ?: $scratch,
  'VORTEX_FETCH_DB_LAGOON_DB_FILE' => getenv('VORTEX_FETCH_DB_LAGOON_DB_FILE') ?: 'db.sql',
  'VORTEX_LAGOONCLI_PATH' => getenv('VORTEX_LAGOONCLI_PATH') ?: $scratch,
  'VORTEX_FETCH_DB_FRESH' => $fresh ? '1' : '',
];

foreach ($config as $name => $value) {
  putenv($name . '=' . $value);
}

echo "Configuration:\n";
echo "  Project:      " . $config['VORTEX_FETCH_DB_LAGOON_PROJECT'] . "\n";
echo "  Instance:     " . $config['VORTEX_FETCH_DB_LAGOON_INSTANCE'] . "\n";
echo "  Environment:  " . $config['VORTEX_FETCH_DB_ENVIRONMENT'] . "\n";
echo "  SSH file:     " . $config['VORTEX_FETCH_DB_SSH_FILE'] . " ('false' = ambient identity)\n";
echo "  Download dir: " . $config['VORTEX_FETCH_DB_LAGOON_DB_DIR'] . "\n";
echo "  CLI cache:    " . $config['VORTEX_LAGOONCLI_PATH'] . "\n";
echo "  Fresh dump:   " . ($fresh ? 'yes' : 'no (reuse a recent dump if available)') . "\n\n";

$dest = $config['VORTEX_FETCH_DB_LAGOON_DB_DIR'] . '/' . $config['VORTEX_FETCH_DB_LAGOON_DB_FILE'];
if (is_file($dest)) {
  unlink($dest);
}

echo "Running vortex-fetch-db-lagoon...\n\n";
// Inherit the real stdio streams so the script sees a TTY and keeps its colour
// output; a piped subprocess (passthru) would disable colours.
$descriptors = [0 => STDIN, 1 => STDOUT, 2 => STDERR];
$process = proc_open('php ' . escapeshellarg(__DIR__ . '/../src/vortex-fetch-db-lagoon'), $descriptors, $pipes);
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
