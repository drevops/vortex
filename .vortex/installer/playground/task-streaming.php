#!/usr/bin/env php
<?php

/**
 * @file
 * Playground script to test Task streaming mode with dimmed output.
 *
 * Run: php playground/task-streaming.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DrevOps\VortexInstaller\Task\Task;
use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Output\ConsoleOutput;

$output = new ConsoleOutput();
Tui::init($output);

echo PHP_EOL;
echo "=== Task Streaming Mode Demo ===" . PHP_EOL;
echo PHP_EOL;

// Streaming mode with echo - output should be dimmed.
echo "--- Streaming mode: output via echo ---" . PHP_EOL;
Task::action(
  label: 'Streaming task using echo',
  action: function () {
    echo "Line 1 via echo...\n";
    usleep(500000);
    echo "Line 2 via echo...\n";
    usleep(500000);
    echo "Line 3 via echo...\n";
    usleep(500000);
    return true;
  },
  success: 'Echo streaming completed',
  streaming: true,
);
echo PHP_EOL;

// Streaming mode with Tui::output() - output should be dimmed.
echo "--- Streaming mode: output via Tui::output() ---" . PHP_EOL;
Task::action(
  label: 'Streaming task using Tui::output()',
  action: function () {
    Tui::output()->writeln("Line 1 via Tui::output()...");
    usleep(500000);
    Tui::output()->writeln("Line 2 via Tui::output()...");
    usleep(500000);
    Tui::output()->writeln("Line 3 via Tui::output()...");
    usleep(500000);
    return true;
  },
  success: 'Tui::output() streaming completed',
  streaming: true,
);
echo PHP_EOL;

// Streaming mode with mixed output.
echo "--- Streaming mode: mixed echo and Tui::output() ---" . PHP_EOL;
Task::action(
  label: 'Streaming task with mixed output',
  action: function () {
    echo "Line 1 via echo...\n";
    usleep(400000);
    Tui::output()->writeln("Line 2 via Tui::output()...");
    usleep(400000);
    echo "Line 3 via echo...\n";
    usleep(400000);
    Tui::output()->writeln("Line 4 via Tui::output()...");
    usleep(400000);
    return true;
  },
  success: 'Mixed streaming completed',
  streaming: true,
);
echo PHP_EOL;

// Streaming mode with failure.
echo "--- Streaming mode: failure case ---" . PHP_EOL;
Task::action(
  label: 'Streaming task that fails',
  action: function () {
    echo "Starting process...\n";
    usleep(500000);
    echo "Error encountered!\n";
    usleep(500000);
    return false;
  },
  failure: 'Streaming task failed',
  streaming: true,
);
echo PHP_EOL;

// Task after failure - verify output is restored.
echo "--- Task after failure: verify output restoration ---" . PHP_EOL;
Task::action(
  label: 'Task after failed streaming',
  action: function () {
    echo "This echo should be dimmed\n";
    usleep(500000);
    Tui::output()->writeln("This Tui::output() should also be dimmed");
    usleep(500000);
    return true;
  },
  success: 'Output restoration verified',
  streaming: true,
);
echo PHP_EOL;

// Streaming mode without success message (default "OK").
echo "--- Streaming mode: no success message (default OK) ---" . PHP_EOL;
Task::action(
  label: 'Streaming task without success message',
  action: function () {
    echo "Some output...\n";
    usleep(500000);
    return true;
  },
  streaming: true,
);
echo PHP_EOL;

// Streaming mode with nested spinner (simulates build command with requirements check).
echo "--- Streaming mode: nested spinner (cursor control) ---" . PHP_EOL;
Task::action(
  label: 'Streaming task with nested spinner',
  action: function () {
    // The nested command uses spin() which outputs cursor control sequences.
    \Laravel\Prompts\spin(
      function () {
        usleep(300000);
        usleep(300000);
        usleep(300000);
      },
      'Nested spinner task...'
    );

    echo "AFTER SPINNER 1\n";
    \Laravel\Prompts\spin(
      function () {
        usleep(1000000);
        usleep(1000000);
      },
      'Another nested spinner task...'
    );

    echo "AFTER SPINNER 2\n";

    return true;
  },
  streaming: true,
);
echo PHP_EOL;

// Streaming mode with colors and styles.
echo "--- Streaming mode: colors and styles ---" . PHP_EOL;
Task::action(
  label: 'Streaming task with styled output',
  action: function () {
    Tui::output()->writeln(Tui::green("Green text"));
    usleep(300000);
    Tui::output()->writeln(Tui::blue("Blue text"));
    usleep(300000);
    Tui::output()->writeln(Tui::yellow("Yellow text"));
    usleep(300000);
    Tui::output()->writeln(Tui::underscore("Underscored text"));
    usleep(300000);
    Tui::output()->writeln(Tui::bold("Bold text"));
    usleep(300000);
    Tui::output()->writeln("Mixed: " . Tui::green("green") . " and " . Tui::blue("blue") . " and " . Tui::underscore("underscored"));
    usleep(300000);
    return true;
  },
  success: 'Styled streaming completed',
  streaming: true,
);
echo PHP_EOL;

// Non-streaming task after streaming tasks.
echo "--- Non-streaming task (spinner) after streaming ---" . PHP_EOL;
Task::action(
  label: 'Spinner task after streaming',
  action: function () {
    usleep(1000000);
    return true;
  },
  success: 'Spinner works after streaming',
);
echo PHP_EOL;

echo "=== Demo Complete ===" . PHP_EOL;
echo PHP_EOL;
