#!/usr/bin/env php
<?php

/**
 * @file
 * Manual demo of output formatter functions.
 *
 * Usage: ./playground/formatters.php.
 */

declare(strict_types=1);

namespace DrevOps\VortexTooling;

require_once __DIR__ . '/../src/helpers.php';

echo "=== Output Formatter Demo ===\n\n";

echo "1. note() - Plain note output:\n";
NOTE("This is a plain note message");
NOTE("Note with formatting: %s %d", "text", 123);

echo "\n2. info() - [INFO] Cyan output:\n";
INFO("This is an info message");
INFO("Info with formatting: %s", "formatted text");

echo "\n3. task() - [TASK] Blue output:\n";
TASK("This is a task message");
TASK("Task with formatting: %s", "running task");

echo "\n4. pass() - [ OK ] Green output:\n";
PASS("This is a success message");
PASS("Success with formatting: %s completed", "operation");

echo "\n5. fail_no_exit() - [FAIL] Red output (no exit):\n";
fail_no_exit("This is a failure message (no exit)");
fail_no_exit("Failure with formatting: %s failed", "operation");

echo "\n6. Testing color detection:\n";
NOTE("Colors should appear in terminal with color support");
NOTE("If TERM=dumb or no tput, colors are stripped");
INFO("Current TERM: %s", getenv('TERM') ?: 'not set');

echo "\n7. Multiple formatters in sequence:\n";
TASK("Starting multi-step process");
NOTE("Step 1: Initializing");
NOTE("Step 2: Processing data");
NOTE("Step 3: Validating results");
PASS("Multi-step process completed successfully");

echo "\n=== Demo Complete ===\n";
