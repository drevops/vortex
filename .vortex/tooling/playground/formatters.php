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
note("This is a plain note message");
note("Note with formatting: %s %d", "text", 123);

echo "\n2. info() - [INFO] Cyan output:\n";
info("This is an info message");
info("Info with formatting: %s", "formatted text");

echo "\n3. task() - [TASK] Blue output:\n";
task("This is a task message");
task("Task with formatting: %s", "running task");

echo "\n4. pass() - [ OK ] Green output:\n";
pass("This is a success message");
pass("Success with formatting: %s completed", "operation");

echo "\n5. fail_no_exit() - [FAIL] Red output (no exit):\n";
fail_no_exit("This is a failure message (no exit)");
fail_no_exit("Failure with formatting: %s failed", "operation");

echo "\n6. Testing color detection:\n";
note("Colors should appear in terminal with color support");
note("If TERM=dumb or no tput, colors are stripped");
info("Current TERM: %s", getenv('TERM') ?: 'not set');

echo "\n7. Multiple formatters in sequence:\n";
task("Starting multi-step process");
note("Step 1: Initializing");
note("Step 2: Processing data");
note("Step 3: Validating results");
pass("Multi-step process completed successfully");

echo "\n=== Demo Complete ===\n";
