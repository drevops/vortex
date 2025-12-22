<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use AlexSkrypnyk\File\ContentFile\ContentFile;
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
      '/SECURITY.md',
      '/.vortex/docs',
      '/.vortex/tests',
    ];
  }

  /**
   * Queue up a content replacement in a file.
   *
   * @param string|array|callable $replacements
   *   Replacements to perform. It can be a string, an associative array
   *   of search and replace pairs, or a callable function that takes
   *   content and file as parameters and returns the modified content.
   * @param string $replace
   *   Replacement string.
   *   If $replacements is a string, this parameter is used as the replacement
   *   value for the search string.
   */
  public static function replaceContentAsync(callable|array|string $replacements, ?string $replace = NULL): void {
    static::addDirectoryTask(function (ContentFile $file) use ($replacements, $replace): ContentFile {
      $content = $file->getContent();

      if (is_callable($replacements)) {
        $content = $replacements($content, $file);
      }
      else {
        if (is_string($replacements)) {
          if ($replace === NULL) {
            throw new \InvalidArgumentException('If $replacements is a string, $replace must be provided.');
          }

          $replacements = [$replacements => $replace];
        }

        foreach ($replacements as $search => $replace_value) {
          $content = static::replaceContent($content, $search, $replace_value);
        }
      }

      $file->setContent($content);
      return $file;
    });
  }

  /**
   * Queue up a token removal from a file.
   *
   * @param string $token
   *   Token to remove.
   * @param bool $with_content
   *   If TRUE, remove content between the start and end tokens.
   */
  public static function removeTokenAsync(string $token, bool $with_content = TRUE): void {
    static::addDirectoryTask(function (ContentFile $file) use ($token, $with_content): ContentFile {
      $content = $file->getContent();
      $content = static::removeToken($content, '#;< ' . $token, '#;> ' . $token, $with_content);
      $file->setContent($content);
      return $file;
    });
  }

  /**
   * Convert a path to a relative path.
   *
   * @param string $path
   *   The path to convert.
   * @param string|null $base
   *   The base path to resolve relative paths against. If NULL, the current
   *   working directory is used.
   *
   * @return string
   *   The relative path.
   */
  public static function toRelative(string $path, ?string $base = NULL): string {
    $base ??= (string) getcwd();
    $absolute = static::absolute($path, $base);

    return str_replace($base . DIRECTORY_SEPARATOR, '', $absolute);
  }

}
