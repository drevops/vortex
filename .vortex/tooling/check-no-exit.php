#!/usr/bin/env php
<?php

/**
 * @file
 * Check for exit() usage and recommend quit() instead.
 *
 * This script scans PHP files for exit() usage and reports violations.
 * The quit() function should be used instead for testability.
 */

declare(strict_types=1);

// Directories to scan (relative to current working directory).
$directories = ['src'];

// Pattern to match exit usage: exit; or exit( or exit (.
$exit_pattern = '/\b(exit)\s*(\(|;)/';

$errors = [];
$script_dir = getcwd();

foreach ($directories as $directory) {
  $full_path = $script_dir . '/' . $directory;

  if (!is_dir($full_path)) {
    continue;
  }

  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($full_path, RecursiveDirectoryIterator::SKIP_DOTS)
  );

  foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
      continue;
    }

    if ($file->getFilename() === 'helpers.php') {
      continue;
    }

    $content = file_get_contents($file->getPathname());

    // Remove all comments before scanning.
    $content_without_comments = preg_replace([
    // Remove /* */ comments.
      '~/\*.*?\*/~s',
    // Remove // comments.
      '~//.*$~m',
    // Remove * docblock lines.
      '~^\s*\*.*$~m',
    // Remove # comments.
      '~^\s*#.*$~m',
    ], '', $content);

    $lines = explode("\n", (string) $content_without_comments);

    foreach ($lines as $line_num => $line) {
      if (preg_match($exit_pattern, $line)) {
        $errors[] = sprintf(
          "%s:%d - Use quit() instead of exit() for testability. The quit() function can be mocked in tests while exit() cannot.",
          $file->getPathname(),
          $line_num + 1
        );
      }
    }
  }
}

if ($errors !== []) {
  foreach ($errors as $error) {
    echo $error . PHP_EOL;
  }
  exit(1);
}

exit(0);
