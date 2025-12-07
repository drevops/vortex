<?php

/**
 * @file
 * Fixture file containing quit() usage (correct).
 */

declare(strict_types=1);

namespace DrevOps\VortexTooling;

function good_function(): bool {
  if (some_condition()) {
    quit(1);
  }

  if (another_condition()) {
    quit(0);
  }

  return TRUE;
}

function some_condition(): bool {
  return FALSE;
}

function another_condition(): bool {
  return FALSE;
}
