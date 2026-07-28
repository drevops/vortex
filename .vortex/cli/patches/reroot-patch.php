<?php

declare(strict_types=1);

/**
 * Re-root a `diff -ruN` output into the `a/` / `b/` form composer-patches
 * expects. Strips trailing timestamps from `--- ` / `+++ ` header lines.
 *
 * Usage:
 *   php reroot-patch.php <source.patch> <target.patch> <upstream-prefix> <fork-prefix>
 *
 * Example:
 *   php .vortex/installer/patches/reroot-patch.php \
 *     /tmp/raw.patch \
 *     .vortex/installer/patches/laravel-prompts-fork-parity.patch \
 *     /tmp/prompts-upstream/ \
 *     /tmp/prompts-fork/
 *
 * The prefixes must end with a slash and correspond to the two directories
 * passed to `diff -ruN` when generating the source patch.
 */

if ($argc !== 5) {
  fwrite(STDERR, "Usage: php reroot-patch.php <source> <target> <upstream-prefix> <fork-prefix>\n");
  exit(2);
}

[$_, $source, $target, $upstream_prefix, $fork_prefix] = $argv;

if (!str_ends_with($upstream_prefix, '/') || !str_ends_with($fork_prefix, '/')) {
  fwrite(STDERR, "Prefix arguments must end with a slash.\n");
  exit(2);
}

$lines = file($source, FILE_IGNORE_NEW_LINES);
if ($lines === FALSE) {
  fwrite(STDERR, sprintf("Failed to read source patch: %s\n", $source));
  exit(1);
}

$out = [];

foreach ($lines as $line) {
  if (str_starts_with($line, 'diff -ruN ')) {
    $line = str_replace($upstream_prefix, 'a/', $line);
    $line = str_replace($fork_prefix, 'b/', $line);
    $out[] = $line;

    continue;
  }

  if (str_starts_with($line, '--- ')) {
    $path = substr($line, 4);
    $tab_pos = strpos($path, "\t");
    if ($tab_pos !== FALSE) {
      $path = substr($path, 0, $tab_pos);
    }
    $path = str_replace($upstream_prefix, 'a/', $path);
    $out[] = '--- ' . $path;

    continue;
  }

  if (str_starts_with($line, '+++ ')) {
    $path = substr($line, 4);
    $tab_pos = strpos($path, "\t");
    if ($tab_pos !== FALSE) {
      $path = substr($path, 0, $tab_pos);
    }
    $path = str_replace($fork_prefix, 'b/', $path);
    $out[] = '+++ ' . $path;

    continue;
  }

  $out[] = $line;
}

$result = implode("\n", $out) . "\n";

if (file_put_contents($target, $result) === FALSE) {
  fwrite(STDERR, sprintf("Failed to write target patch: %s\n", $target));
  exit(1);
}

fwrite(STDOUT, sprintf("Wrote %d lines to %s\n", count($out), $target));
