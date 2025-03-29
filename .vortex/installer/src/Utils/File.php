<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use AlexSkrypnyk\File\File as UpstreamFile;

class File extends UpstreamFile {

  /**
   * Get list of paths to ignore.
   *
   * @return array<int, string>
   *   Array of paths to ignore.
   */
  public static function ignoredPaths(array $paths = []): array {
    return array_merge(parent::ignoredPaths($paths), static::internalPaths());
  }

  /**
   * Check if path is internal.
   */
  public static function isInternal(string $path): bool {
    if (str_starts_with($path, '.' . DIRECTORY_SEPARATOR)) {
      $path = DIRECTORY_SEPARATOR . substr($path, 2);
    }

    return in_array($path, static::internalPaths());
  }

  /**
   * Get list of internal paths.
   */
  protected static function internalPaths(): array {
    return [
      '/LICENSE',
      '/CODE_OF_CONDUCT.md',
      '/CONTRIBUTING.md',
      '/LICENSE',
      '/SECURITY.md',
      '/.vortex/docs',
      '/.vortex/tests',
    ];
  }

}
