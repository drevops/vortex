<?php

declare(strict_types=1);

namespace DrevOps\Tui\Discovery;

/**
 * Base discovery rule with shared path handling.
 *
 * @package DrevOps\Tui\Discovery
 */
abstract class AbstractDiscover implements DiscoverInterface {

  /**
   * Join a directory and a relative path.
   *
   * @param string $directory
   *   The base directory.
   * @param string $relative
   *   The relative path.
   *
   * @return string
   *   The joined path.
   */
  protected function join(string $directory, string $relative): string {
    return rtrim($directory, '/') . '/' . ltrim($relative, '/');
  }

}
