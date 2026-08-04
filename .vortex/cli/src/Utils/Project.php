<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Utils;

/**
 * Helpers for inspecting a destination project directory.
 *
 * @package DrevOps\VortexCli\Utils
 */
class Project {

  /**
   * The pattern identifying a directory as an installed Vortex project.
   *
   * The README badge is the one artifact every installed project carries
   * regardless of the answers given during installation, so it is the only
   * reliable marker available before any file is read.
   */
  const MARKER = '/badge\/Vortex-/';

  /**
   * Check whether a directory holds an installed Vortex project.
   *
   * @param string $dir
   *   The directory to inspect.
   *
   * @return bool
   *   TRUE when the directory's README carries the Vortex badge.
   */
  public static function isVortex(string $dir): bool {
    return File::contains(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'README.md', static::MARKER);
  }

}
