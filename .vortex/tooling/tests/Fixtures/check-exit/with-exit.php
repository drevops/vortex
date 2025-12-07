<?php

/**
 * @file
 * Fixture file containing exit() usage.
 */

declare(strict_types=1);

function bad_function(): bool {
  if (some_condition()) {
    exit(1);
  }

  if (another_condition()) {
    exit;
  }

  return TRUE;
}

function some_condition(): bool {
  return FALSE;
}

function another_condition(): bool {
  return FALSE;
}
