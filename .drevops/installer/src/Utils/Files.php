<?php

namespace DrevOps\Installer\Utils;

/**
 * Files utility.
 */
class Files {

  /**
   * Copy a file, or recursively copy a folder and its contents.
   *
   * @param string $source
   *   Source path.
   * @param string $dest
   *   Destination path.
   * @param int $permissions
   *   New folder creation permissions.
   * @param bool $copy_empty_dirs
   *   Whether to copy empty directories.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
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
      chdir($parent);
      $ret = TRUE;
      if (!is_readable(basename($dest))) {
        $ret = symlink(readlink($source), basename($dest));
      }
      chdir($cur_dir);

      return $ret;
    }

    if (is_file($source)) {
      $ret = copy($source, $dest);
      if ($ret) {
        chmod($dest, fileperms($source));
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
      self::copyRecursive(sprintf('%s/%s', $source, $entry), sprintf('%s/%s', $dest, $entry), $permissions, FALSE);
    }

    $dir && $dir->close();

    return TRUE;
  }

  /**
   * Remove a directory and its contents.
   *
   * @param string $directory
   *   Directory path.
   * @param array $options
   *   Options.
   */
  public static function rmdirRecursiveEmpty(string $directory, $options = []): void {
    if (self::dirIsEmpty($directory)) {
      self::rmdirRecursive($directory, $options);
      self::rmdirRecursiveEmpty(dirname($directory), $options);
    }
  }

  /**
   * Create a temporary directory.
   *
   * @param string|null $dir
   *   The directory where the temporary directory should be created.
   * @param string $prefix
   *   The prefix of the directory.
   * @param int $mode
   *   The permissions of the directory.
   * @param int $max_attempts
   *   The maximum number of attempts to create the directory.
   *
   * @return false|string
   *   The path of the created directory or FALSE on failure.
   */
  public static function tempdir(?string $dir = NULL, string $prefix = 'tmp_', int $mode = 0700, int $max_attempts = 1000): false|string {
    if (is_null($dir)) {
      $dir = sys_get_temp_dir();
    }

    $dir = rtrim($dir, DIRECTORY_SEPARATOR);

    if (!is_dir($dir) || !is_writable($dir)) {
      return FALSE;
    }

    if (strpbrk($prefix, '\\/:*?"<>|') !== FALSE) {
      return FALSE;
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

  /**
   * Replace string in filenames.
   *
   * @param string $search
   *   The value being searched for, otherwise known as the needle.
   * @param string $replace
   *   The replacement value that replaces found search values.
   * @param string $dir
   *   The directory where the replacement should be done.
   */
  public static function replaceStringFilename($search, $replace, string $dir): void {
    $files = self::scandirRecursive($dir, self::ignorePaths());
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

  /**
   * Check if a directory is empty.
   *
   * @param string $directory
   *   The directory path.
   *
   * @return bool
   *   Whether the directory is empty.
   */
  public static function dirIsEmpty($directory): bool {
    return is_dir($directory) && count(scandir($directory)) === 2;
  }

  /**
   * Recursively scan a directory for files.
   *
   * @param string $dir
   *   The directory path.
   * @param array $ignore_paths
   *   The paths to ignore.
   * @param bool $should_include_dirs
   *   Whether to include directories in the result.
   *
   * @return array
   *   The discovered files.
   */
  public static function scandirRecursive(string $dir, array $ignore_paths = [], bool $should_include_dirs = FALSE): array {
    $discovered = [];

    if (is_dir($dir)) {
      $paths = array_diff(scandir($dir), ['.', '..']);
      foreach ($paths as $path) {
        $path = $dir . '/' . $path;
        foreach ($ignore_paths as $ignore_path) {
          // Exlude based on sub-path match.
          if (str_contains($path, (string) $ignore_path)) {
            continue(2);
          }
        }
        if (is_dir($path)) {
          if ($should_include_dirs) {
            $discovered[] = $path;
          }
          $discovered = array_merge($discovered, self::scandirRecursive($path, $ignore_paths, $should_include_dirs));
        }
        else {
          $discovered[] = $path;
        }
      }
    }

    return $discovered;
  }

  /**
   * Check if a file contains a string.
   *
   * @param string $needle
   *   The value being searched for.
   * @param string $file
   *   The file path.
   *
   * @return int|bool
   *   The number of times the needle substring occurs in the haystack string.
   */
  public static function fileContains($needle, $file): int|bool {
    if (!is_readable($file)) {
      return FALSE;
    }

    $content = file_get_contents($file);

    if (Strings::isRegex($needle)) {
      return preg_match($needle, $content);
    }

    return str_contains($content, (string) $needle);
  }

  /**
   * Replace content in files.
   *
   * @param string $needle
   *   The value being searched for.
   * @param string $replacement
   *   The replacement value that replaces found search values.
   * @param string $dir
   *   The directory where the replacement should be done.
   */
  public static function dirReplaceContent($needle, $replacement, string $dir): void {
    $files = self::scandirRecursive($dir, self::ignorePaths());
    foreach ($files as $filename) {
      self::fileReplaceContent($needle, $replacement, $filename);
    }
  }

  /**
   * Replace content in a file.
   *
   * @param string $needle
   *   The value being searched for.
   * @param string $replacement
   *   The replacement value that replaces found search values.
   * @param string $filename
   *   The file path.
   */
  public static function fileReplaceContent($needle, $replacement, $filename) {
    if (!is_readable($filename) || self::fileIsExcludedFromProcessing($filename)) {
      return FALSE;
    }

    $content = file_get_contents($filename);

    if (Strings::isRegex($needle)) {
      $replaced = preg_replace($needle, (string) $replacement, $content);
    }
    else {
      $replaced = str_replace($needle, $replacement, $content);
    }
    if ($replaced != $content) {
      file_put_contents($filename, $replaced);
    }
  }

  /**
   * Recursively remove a directory and its contents.
   *
   * @param string $directory
   *   The directory path.
   * @param array $options
   *   Options.
   */
  public static function rmdirRecursive($directory, array $options = []): void {
    if (!isset($options['traverseSymlinks'])) {
      $options['traverseSymlinks'] = FALSE;
    }
    $items = glob($directory . DIRECTORY_SEPARATOR . '{,.}*', GLOB_MARK | GLOB_BRACE);
    foreach ($items as $item) {
      if (basename($item) == '.' || basename($item) == '..') {
        continue;
      }
      if (substr($item, -1) == DIRECTORY_SEPARATOR) {
        if (!$options['traverseSymlinks'] && is_link(rtrim($item, DIRECTORY_SEPARATOR))) {
          unlink(rtrim($item, DIRECTORY_SEPARATOR));
        }
        else {
          self::rmdirRecursive($item, $options);
        }
      }
      else {
        unlink($item);
      }
    }
    if (is_dir($directory = rtrim((string) $directory, '\\/'))) {
      if (is_link($directory)) {
        unlink($directory);
      }
      else {
        rmdir($directory);
      }
    }
  }

  /**
   * Check if a directory contains a string.
   *
   * @param string $needle
   *   The value being searched for.
   * @param string $dir
   *   The directory path.
   *
   * @return bool
   *   Whether the directory contains the string.
   */
  public static function dirContains($needle, string $dir): bool {
    $files = self::scandirRecursive($dir, self::ignorePaths());
    foreach ($files as $filename) {
      if (self::fileContains($needle, $filename)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get the value of a key from composer.json.
   *
   * @param string $name
   *   The key name.
   * @param string $dir
   *   The directory path.
   *
   * @return mixed|null
   *   The value of the key or NULL if the key does not exist.
   */
  public static function getComposerJsonValue($name, string $dir) {
    $composer_json = $dir . DIRECTORY_SEPARATOR . 'composer.json';
    if (is_readable($composer_json)) {
      $json = json_decode(file_get_contents($composer_json), TRUE);
      if (isset($json[$name])) {
        return $json[$name];
      }
    }

    return NULL;
  }

  /**
   * Find a matching path.
   *
   * @param string|array $paths
   *   The path or paths.
   * @param string|null $text
   *   The text to search for.
   *
   * @return string|null
   *   The matching path or NULL if no match is found.
   */
  public static function findMatchingPath($paths, $text = NULL) {
    $paths = is_array($paths) ? $paths : [$paths];

    foreach ($paths as $path) {
      $files = glob($path);
      if (empty($files)) {
        continue;
      }

      if (count($files)) {
        if (!empty($text)) {
          foreach ($files as $file) {
            if (self::fileContains($text, $file)) {
              return $file;
            }
          }
        }
        else {
          return reset($files);
        }
      }
    }

    return NULL;
  }

  /**
   * Ignore paths.
   *
   * @return array
   *   The paths to ignore.
   */
  public static function ignorePaths(): array {
    return array_merge([
      '/.git/',
      '/.idea/',
      '/vendor/',
      '/node_modules/',
      '/.data/',
    ], self::internalPaths());
  }

  /**
   * Internal paths.
   *
   * @return array
   *   The internal paths.
   */
  public static function internalPaths(): array {
    return [
      '/scripts/drevops/installer/install',
      '/LICENSE',
      '/scripts/drevops/docs',
      '/scripts/drevops/tests',
      '/scripts/drevops/utils',
    ];
  }

  /**
   * Check if a path is internal.
   *
   * @param string $relative_path
   *   The relative path.
   *
   * @return bool
   *   Whether the path is internal.
   */
  public static function isInternalPath(string $relative_path): bool {
    $relative_path = '/' . ltrim($relative_path, './');

    return in_array($relative_path, Files::internalPaths());
  }

  /**
   * Check if a file is excluded from processing.
   *
   * @param string $filename
   *   The filename.
   *
   * @return int|false
   *   The result of the match or FALSE if the file is not excluded.
   */
  public static function fileIsExcludedFromProcessing($filename): int|false {
    $excluded_patterns = [
      '.+\.png',
      '.+\.jpg',
      '.+\.jpeg',
      '.+\.bpm',
      '.+\.tiff',
    ];

    return preg_match('/^(' . implode('|', $excluded_patterns) . ')$/', (string) $filename);
  }

  /**
   * Glob recursively.
   *
   * @param string $pattern
   *   The pattern.
   * @param int $flags
   *   The flags.
   *
   * @return array|false
   *   The result of the glob or FALSE if no match is found.
   */
  public static function globRecursive($pattern, $flags = 0): array|false {
    $files = glob($pattern, $flags | GLOB_BRACE);
    foreach (glob(dirname((string) $pattern) . '/{,.}*[!.]', GLOB_BRACE | GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
      $files = array_merge($files, Files::globRecursive($dir . '/' . basename((string) $pattern), $flags));
    }

    return $files;
  }

  /**
   * Remove a file.
   *
   * @param string $file
   *   The file path.
   */
  public static function remove($file): void {
    @unlink($file);
  }

}
