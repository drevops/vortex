<?php

declare(strict_types=1);

namespace DrevOps\Installer;

/**
 * File utility.
 *
 * File utility functions.
 *
 * @package DrevOps\Installer
 */
class File {

  /**
   * Recursively scan directory for files.
   *
   * @param string $dir
   *   Directory to scan.
   * @param array<int, string> $ignore_paths
   *   Array of paths to ignore.
   * @param bool $include_dirs
   *   Include directories in the result.
   *
   * @return array<int, string>
   *   Array of discovered files.
   */
  public static function scandirRecursive(string $dir, array $ignore_paths = [], bool $include_dirs = FALSE): array {
    $discovered = [];

    if (is_dir($dir)) {
      $files = scandir($dir);
      if (empty($files)) {
        return [];
      }

      $paths = array_diff($files, ['.', '..']);

      foreach ($paths as $path) {
        $path = $dir . '/' . $path;

        foreach ($ignore_paths as $ignore_path) {
          // Exlude based on sub-path match.
          if (str_contains($path, (string) $ignore_path)) {
            continue(2);
          }
        }

        if (is_dir($path)) {
          if ($include_dirs) {
            $discovered[] = $path;
          }
          $discovered = array_merge($discovered, File::scandirRecursive($path, $ignore_paths, $include_dirs));
        }
        else {
          $discovered[] = $path;
        }
      }
    }

    return $discovered;
  }

  /**
   * Get list of paths to ignore.
   *
   * @return array<int, string>
   *   Array of paths to ignore.
   */
  public static function ignorePaths(): array {
    return array_merge([
      '/.git/',
      '/.idea/',
      '/vendor/',
      '/node_modules/',
      '/.data/',
    ], File::internalPaths());
  }

  /**
   * Remove directory recursively.
   *
   * @param string $directory
   *   Directory to remove.
   * @param array<string,mixed> $options
   *   Options to pass.
   */
  public static function rmdirRecursive(string $directory, array $options = []): void {
    if (!isset($options['traverseSymlinks'])) {
      $options['traverseSymlinks'] = FALSE;
    }

    $files = glob($directory . DIRECTORY_SEPARATOR . '{,.}*', GLOB_MARK | GLOB_BRACE);
    if (!empty($files)) {

      foreach ($files as $file) {
        if (basename($file) === '.' || basename($file) === '..') {
          continue;
        }

        if (substr($file, -1) === DIRECTORY_SEPARATOR) {
          if (!$options['traverseSymlinks'] && is_link(rtrim($file, DIRECTORY_SEPARATOR))) {
            unlink(rtrim($file, DIRECTORY_SEPARATOR));
          }
          else {
            File::rmdirRecursive($file, $options);
          }
        }
        else {
          unlink($file);
        }
      }
    }

    if (is_dir($directory = rtrim($directory, '\\/'))) {
      if (is_link($directory)) {
        unlink($directory);
      }
      else {
        rmdir($directory);
      }
    }
  }

  public static function replaceStringFilename(string $search, string $replace, string $dir): void {
    $files = File::scandirRecursive($dir, File::ignorePaths());

    foreach ($files as $filename) {
      $new_filename = str_replace($search, $replace, (string) $filename);

      if ($filename != $new_filename) {
        $new_dir = dirname($new_filename);

        if (!is_dir($new_dir)) {
          mkdir($new_dir, 0777, TRUE);
        }

        rename($filename, $new_filename);
      }
    }
  }

  public static function dirReplaceContent(string $needle, string $replacement, string $dir): void {
    $files = File::scandirRecursive($dir, File::ignorePaths());
    foreach ($files as $filename) {
      File::fileReplaceContent($needle, $replacement, $filename);
    }
  }

  /**
   * Check if path is internal.
   *
   * @param string $path
   *   Path to check.
   *
   * @return bool
   *   TRUE if path is internal, FALSE otherwise.
   */
  public static function isInternalPath(string $path): bool {
    $path = '/' . ltrim($path, './');

    return in_array($path, File::internalPaths());
  }

  public static function fileReplaceContent(string $needle, string $replacement, string $filename): void {
    if (!is_readable($filename) || File::fileIsExcludedFromProcessing($filename)) {
      return;
    }

    $content = file_get_contents($filename);
    if (!$content) {
      return;
    }

    if (File::isRegex($needle)) {
      $replaced = preg_replace($needle, $replacement, $content);
    }
    else {
      $replaced = str_replace($needle, $replacement, $content);
    }
    if ($replaced != $content) {
      file_put_contents($filename, $replaced);
    }
  }

  public static function isRegex(string $str): bool {
    if ($str === '' || strlen($str) < 3) {
      return FALSE;
    }

    return @preg_match($str, '') !== FALSE;
  }

  /**
   * Get list of internal paths.
   *
   * @return array<int, string>
   *   Array of internal paths.
   */
  public static function internalPaths(): array {
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

  public static function copyRecursive(string $source, string $dest, int $permissions = 0755, bool $copy_empty_dirs = FALSE): bool {
    $parent = dirname($dest);

    if (!is_dir($parent)) {
      mkdir($parent, $permissions, TRUE);
    }

    // Note that symlink target must exist.
    if (is_link($source)) {
      // Changing dir symlink will be relevant to the current destination's file
      // directory.
      $cur_dir = getcwd();

      if (!$cur_dir) {
        throw new \RuntimeException('Unable to determine current working directory.');
      }

      chdir($parent);
      $ret = TRUE;

      if (!is_readable(basename($dest))) {
        $link = readlink($source);
        if ($link) {
          $ret = symlink($link, basename($dest));
        }
      }

      chdir($cur_dir);

      return $ret;
    }

    if (is_file($source)) {
      $ret = copy($source, $dest);
      if ($ret) {
        $perms = fileperms($source);
        if ($perms !== FALSE) {
          chmod($dest, $perms);
        }
      }

      return $ret;
    }

    if (!is_dir($dest) && $copy_empty_dirs) {
      mkdir($dest, $permissions, TRUE);
    }

    $dir = dir($source);
    while ($dir && FALSE !== $entry = $dir->read()) {
      if ($entry == '.' || $entry == '..') {
        continue;
      }
      File::copyRecursive(sprintf('%s/%s', $source, $entry), sprintf('%s/%s', $dest, $entry), $permissions, FALSE);
    }

    $dir && $dir->close();

    return TRUE;
  }

  /**
   * Check if file is excluded from processing.
   *
   * @param string $filename
   *   Filename to check.
   *
   * @return bool
   *   TRUE if file is excluded, FALSE otherwise.
   */
  public static function fileIsExcludedFromProcessing(string $filename): bool {
    $excluded_patterns = [
      '.+\.png',
      '.+\.jpg',
      '.+\.jpeg',
      '.+\.bpm',
      '.+\.tiff',
    ];

    return (bool) preg_match('/^(' . implode('|', $excluded_patterns) . ')$/', $filename);
  }

  public static function dirContains(string $needle, string $dir): bool {
    $files = File::scandirRecursive($dir, File::ignorePaths());
    foreach ($files as $filename) {
      if (File::fileContains($needle, $filename)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  public static function fileContains(string $needle, string $filename): bool {
    if (!is_readable($filename)) {
      return FALSE;
    }

    $content = file_get_contents($filename);
    if (!$content) {
      return FALSE;
    }

    if (File::isRegex($needle)) {
      return (bool) preg_match($needle, $content);
    }

    return str_contains($content, $needle);
  }

  /**
   * Remove directory recursively if empty.
   *
   * @param string $directory
   *   Directory to remove.
   * @param array<string,mixed> $options
   *   Options to pass.
   */
  public static function rmdirRecursiveEmpty(string $directory, array $options = []): void {
    if (File::dirIsEmpty($directory)) {
      File::rmdirRecursive($directory, $options);
      File::rmdirRecursiveEmpty(dirname($directory), $options);
    }
  }

  /**
   * Check if directory is empty.
   *
   * @param string $directory
   *   Directory to check.
   *
   * @return bool
   *   TRUE if directory is empty, FALSE otherwise.
   */
  public static function dirIsEmpty(string $directory): bool {
    return is_dir($directory) && count(scandir($directory) ?: []) === 2;
  }

  public static function createTempdir(?string $dir = NULL, string $prefix = 'tmp_', int $mode = 0700, int $max_attempts = 1000): string {
    if (is_null($dir)) {
      $dir = sys_get_temp_dir();
    }

    $dir = rtrim($dir, DIRECTORY_SEPARATOR);

    if (!is_dir($dir) || !is_writable($dir)) {
      throw new \RuntimeException(sprintf('Temporary directory "%s" does not exist or is not writable.', $dir));
    }

    if (strpbrk($prefix, '\\/:*?"<>|') !== FALSE) {
      throw new \InvalidArgumentException('Invalid prefix.');
    }
    $attempts = 0;

    do {
      $path = sprintf('%s%s%s%s', $dir, DIRECTORY_SEPARATOR, $prefix, mt_rand(100000, mt_getrandmax()));
    } while (!mkdir($path, $mode) && $attempts++ < $max_attempts);

    if (!is_dir($path) || !is_writable($path)) {
      throw new \RuntimeException(sprintf('Unable to create temporary directory "%s".', $path));
    }

    return $path;
  }

  public static function removeTokenFromFile(string $filename, string $token_begin, ?string $token_end = NULL, bool $with_content = FALSE): void {
    if (File::fileIsExcludedFromProcessing($filename)) {
      return;
    }

    $token_end = $token_end ?? $token_begin;

    $content = file_get_contents($filename);
    if (!$content) {
      return;
    }

    if ($token_begin !== $token_end) {
      $token_begin_count = preg_match_all('/' . preg_quote($token_begin) . '/', $content);
      $token_end_count = preg_match_all('/' . preg_quote($token_end) . '/', $content);
      if ($token_begin_count !== $token_end_count) {
        throw new \RuntimeException(sprintf('Invalid begin and end token count in file %s: begin is %s(%s), end is %s(%s).', $filename, $token_begin, $token_begin_count, $token_end, $token_end_count));
      }
    }

    $out = [];
    $within_token = FALSE;

    $lines = file($filename);
    if (!$lines) {
      return;
    }

    foreach ($lines as $line) {
      if (str_contains($line, $token_begin)) {
        if ($with_content) {
          $within_token = TRUE;
        }
        continue;
      }
      elseif (str_contains($line, $token_end)) {
        if ($with_content) {
          $within_token = FALSE;
        }
        continue;
      }

      if ($with_content && $within_token) {
        // Skip content as contents of the token.
        continue;
      }

      $out[] = $line;
    }

    file_put_contents($filename, implode('', $out));
  }

  /**
   * Find a matching path using glob.
   *
   * @param array<int, string>|string $paths
   *   Array of paths wildcards to search.
   * @param string|null $text
   *   Optional text to search in the files.
   *
   * @return string|null
   *   Path to the file or NULL if not found.
   */
  public static function findMatchingPath(array|string $paths, ?string $text = NULL): ?string {
    $paths = is_array($paths) ? $paths : [$paths];

    foreach ($paths as $path) {
      $files = glob($path);

      if (empty($files)) {
        continue;
      }

      if (!empty($text)) {
        foreach ($files as $file) {
          if (File::fileContains($text, $file)) {
            return $file;
          }
        }
      }
      else {
        return reset($files);
      }
    }

    return NULL;
  }

  /**
   * Recursively scan directory for files.
   *
   * @param string $pattern
   *   Pattern to search.
   * @param int $flags
   *   Flags to pass to glob.
   *
   * @return array<int, string>
   *   Array of discovered files.
   */
  public static function globRecursive(string $pattern, int $flags = 0): array {
    $files = glob($pattern, $flags | GLOB_BRACE);

    if ($files) {
      $dirs = glob(dirname($pattern) . '/{,.}*[!.]', GLOB_BRACE | GLOB_ONLYDIR | GLOB_NOSORT);
      if ($dirs) {
        foreach ($dirs as $dir) {
          $files = array_merge($files, File::globRecursive($dir . '/' . basename($pattern), $flags));
        }
      }
    }

    return $files ?: [];
  }

  public static function removeTokenWithContent(string $token, string $dir): void {
    $files = File::scandirRecursive($dir, File::ignorePaths());
    foreach ($files as $filename) {
      File::removeTokenFromFile($filename, '#;< ' . $token, '#;> ' . $token, TRUE);
    }
  }

  public static function removeTokenLine(string $token, string $dir): void {
    if (!empty($token)) {
      $files = File::scandirRecursive($dir, File::ignorePaths());
      foreach ($files as $filename) {
        File::removeTokenFromFile($filename, $token, NULL);
      }
    }
  }

}
