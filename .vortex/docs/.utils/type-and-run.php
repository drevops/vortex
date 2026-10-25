#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Simulate a user typing a command at a prompt, then execute it.
 *
 * Used by `update-videos.php` so that each recorded command video starts
 * with the visible prompt + command before the actual output streams in.
 *
 * Usage:
 *   type-and-run.php ahoy lint
 */

$cmd = implode(' ', array_slice($argv, 1));
if ($cmd === '') {
  fwrite(STDERR, "Usage: type-and-run.php <command...>\n");
  exit(1);
}

echo '$ ';
flush();
usleep(500_000);

$len = strlen($cmd);
for ($i = 0; $i < $len; $i++) {
  echo $cmd[$i];
  flush();
  usleep(50_000);
}

usleep(400_000);
echo "\n";
flush();

// The command writes to the terminal itself rather than a pipe, so Docker
// Compose can open a console for its build progress.
$process = proc_open($cmd, [STDIN, STDOUT, STDERR], $pipes);
if (!is_resource($process)) {
  fwrite(STDERR, sprintf("Failed to run: %s\n", $cmd));
  exit(1);
}

exit(proc_close($process));
