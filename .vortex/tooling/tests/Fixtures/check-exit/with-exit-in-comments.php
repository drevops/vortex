<?php

/**
 * @file
 * Fixture file with exit in comments (should be ignored).
 */

declare(strict_types=1);

namespace DrevOps\VortexTooling;

function function_with_exit_in_comments(): void {
  // Don't use exit() - use quit() instead.
  // exit(1) is not testable.
  /* The exit() function should not be used.
   * Use quit() for testability.
   */
  // exit; should be avoided.
  quit(0);
}
