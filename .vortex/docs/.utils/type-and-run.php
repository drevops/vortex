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

passthru($cmd, $exit_code);
exit($exit_code);
