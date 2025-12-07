#!/usr/bin/env php
<?php

/**
 * @file
 * Manual demo of environment loading.
 *
 * Usage: ./playground/env.php.
 *
 * This demonstrates loading both .env and .env.local files from the current
 * directory, with .env.local values overriding .env values.
 */

declare(strict_types=1);

namespace DrevOps\VortexTooling;

require_once __DIR__ . '/../src/helpers.php';

echo "=== Environment Loading Demo ===\n\n";

// Show current directory.
echo "Current directory: " . __DIR__ . "\n\n";

// Check if .env files exist.
$env_file = __DIR__ . '/.env';
$env_local_file = __DIR__ . '/.env.local';

echo "Checking for .env files:\n";
if (file_exists($env_file)) {
  echo "  ✓ .env file found\n";
  echo "    Content preview:\n";
  $file_lines = file($env_file, FILE_IGNORE_NEW_LINES);
  if ($file_lines !== FALSE) {
    $lines = array_slice($file_lines, 0, 5);
    foreach ($lines as $line) {
      echo "      " . $line . "\n";
    }
  }
}
else {
  echo "  ✗ .env file not found\n";
  echo "    Run from playground directory where .env file exists\n";
}

if (file_exists($env_local_file)) {
  echo "  ✓ .env.local file found\n";
  echo "    Content preview:\n";
  $file_lines = file($env_local_file, FILE_IGNORE_NEW_LINES);
  if ($file_lines !== FALSE) {
    $lines = array_slice($file_lines, 0, 5);
    foreach ($lines as $line) {
      echo "      " . $line . "\n";
    }
  }
}
else {
  echo "  ✗ .env.local file not found\n";
  echo "    Create .env.local to test override behavior\n";
}

echo "\n";

// Load environment files.
echo "Loading environment files...\n";
load_dotenv([$env_file, $env_local_file]);
echo "  ✓ Environment files loaded\n";

echo "\n";

// Display loaded variables.
echo "Environment variables loaded:\n";

$test_vars = [
  'PLAYGROUND_VAR_1',
  'PLAYGROUND_VAR_2',
  'PLAYGROUND_VAR_3',
  'PLAYGROUND_QUOTED',
  'PLAYGROUND_WITH_EQUALS',
  'PLAYGROUND_OVERRIDE',
];

foreach ($test_vars as $test_var) {
  $value = getenv($test_var);
  if ($value !== FALSE) {
    echo "  " . $test_var . " = " . $value . "\n";
  }
  else {
    echo "  " . $test_var . " = (not set)\n";
  }
}

echo "\n";

// Verify specific values.
echo "Verification tests:\n";

if (getenv('PLAYGROUND_VAR_1') === 'value1') {
  echo "  ✓ PLAYGROUND_VAR_1 loaded correctly\n";
}
else {
  echo "  ✗ PLAYGROUND_VAR_1 not loaded correctly\n";
}

$quoted_value = getenv('PLAYGROUND_QUOTED');
if ($quoted_value === 'quoted value with spaces' || $quoted_value === 'locally overridden with single quotes') {
  echo "  ✓ PLAYGROUND_QUOTED loaded correctly\n";
  echo "    Value: " . $quoted_value . "\n";
}
else {
  echo "  ✗ PLAYGROUND_QUOTED not loaded correctly\n";
  echo "    Expected: 'quoted value with spaces' or 'locally overridden with single quotes'\n";
  echo "    Got: " . $quoted_value . "\n";
}

if (getenv('PLAYGROUND_WITH_EQUALS') === 'value=with=equals') {
  echo "  ✓ PLAYGROUND_WITH_EQUALS loaded correctly (handles = in value)\n";
}
else {
  echo "  ✗ PLAYGROUND_WITH_EQUALS not loaded correctly\n";
}

// Check override behavior.
if (file_exists($env_local_file)) {
  $override_value = getenv('PLAYGROUND_OVERRIDE');
  if ($override_value === 'overridden_by_local') {
    echo "  ✓ PLAYGROUND_OVERRIDE correctly overridden by .env.local\n";
    echo "    Original value in .env: original_value\n";
    echo "    Overridden value from .env.local: " . $override_value . "\n";
  }
  elseif ($override_value === 'original_value') {
    echo "  ✗ PLAYGROUND_OVERRIDE not overridden (still has .env value)\n";
    echo "    Check that .env.local is loaded after .env\n";
  }
  else {
    echo "  ✗ PLAYGROUND_OVERRIDE has unexpected value: " . $override_value . "\n";
  }
}

echo "\n";

// Test comment handling.
echo "Comment handling:\n";
if (getenv('PLAYGROUND_COMMENT') === FALSE) {
  echo "  ✓ Comment lines correctly ignored\n";
}
else {
  echo "  ✗ Comment line was parsed as variable\n";
}

echo "\n=== Demo Complete ===\n";
