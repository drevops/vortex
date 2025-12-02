#!/usr/bin/env php
<?php

/**
 * @file
 * Playground script to demonstrate Task helper with spinners.
 *
 * Run: php playground/task.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DrevOps\VortexInstaller\Task\Task;
use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Output\ConsoleOutput;

$output = new ConsoleOutput();
Tui::init($output);

echo PHP_EOL;
echo "=== Task Helper Demo ===" . PHP_EOL;
echo PHP_EOL;

// Basic task - action returns true, default "OK" success message.
echo "--- Basic: action returns true, default success ---" . PHP_EOL;
Task::action(
  label: 'Label only, action returns true',
  action: function () {
    usleep(500000);
    return true;
  },
);
echo PHP_EOL;

// Action returns a string, success callback receives it.
echo "--- Action returns string, success callback uses it ---" . PHP_EOL;
Task::action(
  label: 'Action returns "result_value"',
  action: function () {
    sleep(1);
    return 'result_value';
  },
  success: fn($result) => "Success received: $result",
);
echo PHP_EOL;

// Action returns integer, success callback uses it.
echo "--- Action returns integer ---" . PHP_EOL;
Task::action(
  label: 'Action returns integer 42',
  action: function () {
    sleep(1);
    return 42;
  },
  success: fn($count) => "Success received integer: $count",
);
echo PHP_EOL;

// Static success message (not a callback).
echo "--- Static success message (string, not callback) ---" . PHP_EOL;
Task::action(
  label: 'Action with static success string',
  action: function () {
    sleep(1);
    return true;
  },
  success: 'Static success message',
);
echo PHP_EOL;

// Task with hint parameter.
echo "--- Hint parameter shown below label ---" . PHP_EOL;
Task::action(
  label: 'Label with hint parameter',
  action: function () {
    sleep(2);
    return true;
  },
  hint: 'This hint appears dimmed below the label',
  success: 'Completed with hint',
);
echo PHP_EOL;

// Action returns false - triggers failure path.
echo "--- Action returns false, triggers failure ---" . PHP_EOL;
Task::action(
  label: 'Action returns false',
  action: function () {
    sleep(1);
    return false;
  },
  failure: 'Custom failure message',
);
echo PHP_EOL;

// Action returns false with default failure message.
echo "--- Action returns false, default failure message ---" . PHP_EOL;
Task::action(
  label: 'Action returns false, no failure param',
  action: function () {
    usleep(500000);
    return false;
  },
);
echo PHP_EOL;

// Action returns array - displayed as sublist.
echo "--- Action returns array, displayed as sublist ---" . PHP_EOL;
Task::action(
  label: 'Action returns array of strings',
  action: function () {
    sleep(1);
    return [
      'Array item 1',
      'Array item 2',
      'Array item 3',
    ];
  },
  success: 'Array items shown above',
);
echo PHP_EOL;

// Dynamic label from closure.
echo "--- Label as closure (evaluated at runtime) ---" . PHP_EOL;
$dynamic_value = 'dynamic_' . rand(100, 999);
Task::action(
  label: fn() => "Label from closure: $dynamic_value",
  action: function () {
    sleep(1);
    return true;
  },
);
echo PHP_EOL;

// Longer spinner duration.
echo "--- Longer duration (3s) to see spinner animation ---" . PHP_EOL;
Task::action(
  label: 'Spinner runs for 3 seconds',
  action: function () {
    sleep(3);
    return true;
  },
  success: 'Spinner completed',
);
echo PHP_EOL;

// Very short duration.
echo "--- Very short duration (100ms) ---" . PHP_EOL;
Task::action(
  label: 'Spinner for 100ms only',
  action: function () {
    usleep(100000);
    return true;
  },
);
echo PHP_EOL;

// Multiple tasks in sequence.
echo "--- Multiple sequential tasks ---" . PHP_EOL;
for ($i = 1; $i <= 3; $i++) {
  Task::action(
    label: "Sequential task $i of 3",
    action: function () use ($i) {
      usleep($i * 300000);
      return true;
    },
    success: "Task $i done",
  );
}
echo PHP_EOL;

// Hint as closure.
echo "--- Hint as closure (evaluated at runtime) ---" . PHP_EOL;
Task::action(
  label: 'Task with dynamic hint',
  action: function () {
    sleep(1);
    return true;
  },
  hint: fn() => 'Hint from closure: ' . date('H:i:s'),
);
echo PHP_EOL;

// Success as closure receiving null (when action returns true).
echo "--- Success callback receives true (not useful) ---" . PHP_EOL;
Task::action(
  label: 'Action returns true, success gets true',
  action: function () {
    sleep(1);
    return true;
  },
  success: fn($result) => "Success callback got: " . var_export($result, true),
);
echo PHP_EOL;

// Failure as closure.
echo "--- Failure as closure ---" . PHP_EOL;
Task::action(
  label: 'Action returns false, failure is closure',
  action: function () {
    usleep(500000);
    return false;
  },
  failure: fn() => 'Failure from closure: ' . date('H:i:s'),
);
echo PHP_EOL;

// Streaming mode - no spinner, output streams during action.
echo "--- Streaming mode: output streams during action ---" . PHP_EOL;
Task::action(
  label: 'Streaming task with output',
  action: function () {
    echo "Line 1 of output...\n";
    usleep(500000);
    echo "Line 2 of output...\n";
    usleep(500000);
    echo "Line 3 of output...\n";
    usleep(500000);
    return true;
  },
  success: 'Streaming completed',
  streaming: true,
);
echo PHP_EOL;

// Streaming mode with failure.
echo "--- Streaming mode: action returns false ---" . PHP_EOL;
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

// Streaming mode with longer output.
echo "--- Streaming mode: simulated build output ---" . PHP_EOL;
Task::action(
  label: 'Building project',
  action: function () {
    $steps = [
      'Installing dependencies...',
      'Compiling assets...',
      'Running migrations...',
      'Clearing caches...',
      'Build complete.',
    ];
    foreach ($steps as $step) {
      echo "$step\n";
      usleep(400000);
    }
    return true;
  },
  success: 'Project built successfully',
  streaming: true,
);
echo PHP_EOL;

echo "=== Demo Complete ===" . PHP_EOL;
echo PHP_EOL;
