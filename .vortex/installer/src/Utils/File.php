<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use Symfony\Component\Filesystem\Filesystem;

/**
 * File utility.
 *
 * File utility functions.
 *
 * @package DrevOps\Installer
 */
class File {

  const RULE_IGNORE_CONTENT = 'ignore_content';

  const RULE_SKIP = 'skip';

  const RULE_GLOBAL = 'global';

  const RULE_INCLUDE = 'include';

  const IGNORECONTENT = '.ignorecontent';

  public static function cwd(): string {
    return static::absolute($_SERVER['PWD'] ?? (string) getcwd());
  }

  /**
   * Replacement for PHP's `realpath` resolves non-existing paths.
   *
   * The main deference is that it does not return FALSE on non-existing
   * paths.
   *
   * @param string $path
   *   Path that needs to be resolved.
   *
   * @return string
   *   Resolved path.
   *
   * @see https://stackoverflow.com/a/29372360/712666
   */
  public static function realpath(string $path): string {
    // Whether $path is unix or not.
    $is_unix_path = $path === '' || $path[0] !== '/';
    $unc = str_starts_with($path, '\\\\');

    // Attempt to detect if path is relative in which case, add cwd.
    if (!str_contains($path, ':') && $is_unix_path && !$unc) {
      $path = getcwd() . DIRECTORY_SEPARATOR . $path;
      if ($path[0] === '/') {
        $is_unix_path = FALSE;
      }
    }

    // Resolve path parts (single dot, double dot and double delimiters).
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), static function ($part): bool {
      return strlen($part) > 0;
    });

    $absolutes = [];
    foreach ($parts as $part) {
      if ('.' === $part) {
        continue;
      }
      if ('..' === $part) {
        array_pop($absolutes);
      }
      else {
        $absolutes[] = $part;
      }
    }

    $path = implode(DIRECTORY_SEPARATOR, $absolutes);
    // Put initial separator that could have been lost.
    $path = $is_unix_path ? $path : '/' . $path;
    $path = $unc ? '\\\\' . $path : $path;

    // Resolve any symlinks.
    if (function_exists('readlink') && file_exists($path) && is_link($path) > 0) {
      $path = readlink($path);

      if (!$path) {
        // @codeCoverageIgnoreStart
        throw new \Exception(sprintf('Could not resolve symlink for path: %s', $path));
        // @codeCoverageIgnoreEnd
      }
    }

    if (str_starts_with($path, sys_get_temp_dir())) {
      $tmp_realpath = realpath(sys_get_temp_dir());
      if ($tmp_realpath) {
        $path = str_replace(sys_get_temp_dir(), $tmp_realpath, $path);
      }
    }

    return $path;
  }

  /**
   * Get absolute path for provided absolute or relative file.
   */
  public static function absolute(string $file, ?string $base = NULL): string {
    if ((new Filesystem())->isAbsolutePath($file)) {
      return static::realpath($file);
    }

    $base = $base ?: static::cwd();
    $base = static::realpath($base);
    $file = $base . DIRECTORY_SEPARATOR . $file;

    return static::realpath($file);
  }

  public static function exists(string|array $files): bool {
    return (new Filesystem())->exists($files);
  }

  public static function dir(string $directory, bool $create = FALSE, int $permissions = 0777): string {
    $directory = static::realpath($directory);

    if (!is_dir($directory)) {
      if (!$create) {
        throw new \RuntimeException(sprintf('Directory "%s" does not exist.', $directory));
      }

      $directory = static::absolute($directory);
      if (static::exists($directory)) {
        if (!is_dir($directory)) {
          throw new \RuntimeException(sprintf('Directory "%s" is a file.', $directory));
        }
      }
      else {
        (new Filesystem())->mkdir($directory, $permissions);
        if (!is_readable($directory) || !is_dir($directory)) {
          // @codeCoverageIgnoreStart
          throw new \RuntimeException(sprintf('Unable to create directory "%s".', $directory));
          // @codeCoverageIgnoreEnd
        }
      }
    }

    return $directory;
  }

  public static function dirIsEmpty(string $directory): bool {
    return is_dir($directory) && count(scandir($directory) ?: []) === 2;
  }

  public static function tmpdir(?string $directory = NULL, string $prefix = 'tmp_', int $mode = 0700, int $max_attempts = 1000): string {
    $directory = $directory ?: sys_get_temp_dir();
    $directory = rtrim($directory, DIRECTORY_SEPARATOR);
    static::dir($directory, TRUE);

    if (strpbrk($prefix, '\\/:*?"<>|') !== FALSE) {
      // @codeCoverageIgnoreStart
      throw new \InvalidArgumentException('Invalid prefix.');
      // @codeCoverageIgnoreEnd
    }
    $attempts = 0;

    do {
      $path = sprintf('%s%s%s%s', $directory, DIRECTORY_SEPARATOR, $prefix, mt_rand(100000, mt_getrandmax()));
    } while (!mkdir($path, $mode) && $attempts++ < $max_attempts);

    if (!is_dir($path) || !is_writable($path)) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException(sprintf('Unable to create temporary directory "%s".', $path));
      // @codeCoverageIgnoreEnd
    }

    return static::realpath($path);
  }

  public static function findMatchingPath(array|string $paths, ?string $needle = NULL): ?string {
    $paths = is_array($paths) ? $paths : [$paths];

    foreach ($paths as $path) {
      $files = glob($path);

      if (empty($files)) {
        continue;
      }

      if (!empty($needle)) {
        foreach ($files as $file) {
          if (static::contains($file, $needle)) {
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
   * Sync files from one directory to another, respecting the .ignorecontent.
   */
  public static function sync(string $src, string $dst, ?callable $before_match_content = NULL, int $permissions = 0755, bool $copy_empty_dirs = FALSE): void {
    if (is_file($dst)) {
      throw new \RuntimeException('Destination is a file and already exists');
    }

    $dst = static::dir($dst, TRUE);

    // Setup rules using the .ignorecontent file from the destination directory.
    $rules = self::contentignore($dst . DIRECTORY_SEPARATOR . self::IGNORECONTENT);
    $rules[self::RULE_SKIP] = array_merge($rules[self::RULE_SKIP], [self::IGNORECONTENT, '.git/']);

    $src_files = self::list($src, $rules, $before_match_content);

    foreach (array_keys($src_files) as $file) {
      $absolute_src_path = $src . DIRECTORY_SEPARATOR . $file;
      $absolute_dst_path = $dst . DIRECTORY_SEPARATOR . $file;

      static::copy($absolute_src_path, $absolute_dst_path, $permissions, $copy_empty_dirs);
    }
  }

  public static function copy(string $source, string $dest, int $permissions = 0755, bool $copy_empty_dirs = FALSE): bool {
    $parent = dirname($dest);
    $parent = static::dir($parent, TRUE, $permissions);

    // Note that symlink target must exist.
    if (is_link($source)) {
      // Changing dir symlink will be relevant to the current destination's file
      // directory.
      $cur_dir = static::cwd();

      chdir($parent);
      $ret = TRUE;

      if (!is_readable(basename($dest))) {
        $link = readlink($source);
        if ($link) {
          try {
            (new Filesystem())->symlink($link, basename($dest));
          }
          // @codeCoverageIgnoreStart
          catch (\Exception $e) {
            $ret = FALSE;
          }
          // @codeCoverageIgnoreEnd
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
      static::copy(sprintf('%s/%s', $source, $entry), sprintf('%s/%s', $dest, $entry), $permissions, FALSE);
    }

    $dir && $dir->close();

    return TRUE;
  }

  /**
   * Parse .ignorecontent file into rules.
   */
  public static function contentignore(string $file): array {
    $rules = [
      self::RULE_INCLUDE => [],
      self::RULE_IGNORE_CONTENT => [],
      self::RULE_GLOBAL => [],
      self::RULE_SKIP => [],
    ];

    if (!file_exists($file)) {
      return $rules;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === FALSE) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException(sprintf('Failed to read the %s file.', self::IGNORECONTENT));
      // @codeCoverageIgnoreEnd
    }

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line[0] === '#') {
        continue;
      }
      elseif ($line[0] === '!') {
        $rules[self::RULE_INCLUDE][] = $line[1] === '^' ? substr($line, 2) : substr($line, 1);
      }
      elseif ($line[0] === '^') {
        $rules[self::RULE_IGNORE_CONTENT][] = substr($line, 1);
      }
      elseif (!str_contains($line, DIRECTORY_SEPARATOR)) {
        // Treat patterns without slashes as global patterns.
        $rules[self::RULE_GLOBAL][] = $line;
      }
      else {
        // Regular skip rule.
        $rules[self::RULE_SKIP][] = $line;
      }
    }

    return $rules;
  }

  /**
   * Recursively scan directory for files.
   *
   * @param string $directory
   *   Directory to scan.
   * @param array<int, string> $ignore_paths
   *   Array of paths to ignore.
   * @param bool $include_dirs
   *   Include directories in the result.
   *
   * @return array<int, string>
   *   Array of discovered files.
   */
  public static function scandirRecursive(string $directory, array $ignore_paths = [], bool $include_dirs = FALSE): array {
    $discovered = [];

    if (is_dir($directory)) {
      $files = scandir($directory);
      if (empty($files)) {
        return [];
      }

      $paths = array_diff($files, ['.', '..']);

      foreach ($paths as $path) {
        $path = $directory . '/' . $path;

        foreach ($ignore_paths as $ignore_path) {
          // Exclude based on sub-path match.
          if (str_contains($path, (string) $ignore_path)) {
            continue(2);
          }
        }

        if (is_dir($path)) {
          if ($include_dirs) {
            $discovered[] = $path;
          }
          $discovered = array_merge($discovered, static::scandirRecursive($path, $ignore_paths, $include_dirs));
        }
        else {
          $discovered[] = $path;
        }
      }
    }

    return $discovered;
  }

  /**
   * Remove directory recursively.
   */
  public static function rmdir(string $directory): void {
    (new Filesystem())->remove($directory);
  }

  /**
   * Remove directory recursively if empty.
   */
  public static function rmdirEmpty(string $directory): void {
    if (static::dirIsEmpty($directory)) {
      static::rmdir($directory);
      static::rmdirEmpty(dirname($directory));
    }
  }

  /**
   * List files in directory respecting rules and optionally using a callback.
   */
  public static function list(string $directory, array $rules = [], ?callable $before_match_content = NULL): array {
    $files = [];

    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
      if (!$file instanceof \SplFileInfo) {
        // @codeCoverageIgnoreStart
        continue;
        // @codeCoverageIgnoreEnd
      }

      $is_directory = $file->isDir();
      $is_link = $file->isLink();

      $pathname = $file->getPathname();
      $path = str_replace($directory . DIRECTORY_SEPARATOR, '', $pathname);
      $path .= $is_directory && !$is_link ? DIRECTORY_SEPARATOR : '';

      foreach ($rules[self::RULE_GLOBAL] ?? [] as $pattern) {
        if (self::isPathMatchesPattern(basename($path), $pattern)) {
          continue 2;
        }
      }

      $is_included = FALSE;
      foreach ($rules[self::RULE_INCLUDE] ?? [] as $pattern) {
        if (self::isPathMatchesPattern($path, $pattern)) {
          $is_included = TRUE;
          break;
        }
      }

      if (!$is_included) {
        foreach ($rules[self::RULE_SKIP] ?? [] as $pattern) {
          if (self::isPathMatchesPattern($path, $pattern)) {
            continue 2;
          }
        }
      }

      $is_ignore_content = FALSE;
      if (!$is_included) {
        foreach ($rules[self::RULE_IGNORE_CONTENT] ?? [] as $pattern) {
          if (self::isPathMatchesPattern($path, $pattern)) {
            $is_ignore_content = TRUE;
            break;
          }
        }
      }

      if ($is_ignore_content) {
        $files[$path] = self::RULE_IGNORE_CONTENT;
      }
      elseif ($is_directory && !$is_link) {
        // @codeCoverageIgnoreStart
        $files[$path] = self::RULE_IGNORE_CONTENT;
        // @codeCoverageIgnoreEnd
      }
      else {
        $rp = $file->getPathname();
        $pathname_real = $file->getRealPath();
        if (!is_readable($rp)) {
          // @codeCoverageIgnoreStart
          continue;
          // @codeCoverageIgnoreEnd
        }
        $content = $is_link ? str_replace($directory . DIRECTORY_SEPARATOR, '', $pathname_real) : (new Filesystem())->readFile($rp);

        $is_matched = TRUE;
        if (is_callable($before_match_content)) {
          $ret = $before_match_content($content, $file);
          $is_matched = $ret !== FALSE;
        }

        if ($content !== FALSE && $is_matched) {
          $files[$path] = md5(trim($content));
        }
      }
    }
    ksort($files);

    return $files;
  }

  public static function dump(string $file, string $content = ''): void {
    (new Filesystem())->dumpFile($file, $content);
  }

  public static function contains(string $file, string $needle): bool {
    if (!is_readable($file)) {
      // @codeCoverageIgnoreStart
      return FALSE;
      // @codeCoverageIgnoreEnd
    }

    $content = (new Filesystem())->readFile($file);
    if ($content === '' || $content === '0') {
      return FALSE;
    }

    if (static::isRegex($needle)) {
      return (bool) preg_match($needle, $content);
    }

    return str_contains($content, $needle);
  }

  public static function containsInDir(string $directory, string $needle, array $excluded = []): array {
    $contains = [];

    $files = static::scandirRecursive($directory, array_merge(static::ignoredPaths(), $excluded));
    foreach ($files as $filename) {
      if (static::contains($filename, $needle)) {
        $contains[] = $filename;
      }
    }

    return $contains;
  }

  public static function renameInDir(string $directory, string $search, string $replace): void {
    $files = static::scandirRecursive($directory, static::ignoredPaths());

    foreach ($files as $filename) {
      $new_filename = str_replace($search, $replace, (string) $filename);

      if ($filename != $new_filename) {
        $new_dir = dirname($new_filename);

        if (!is_dir($new_dir)) {
          mkdir($new_dir, 0777, TRUE);
        }

        rename($filename, $new_filename);

        static::rmdirEmpty(dirname($filename));
      }
    }
  }

  public static function replaceContentInDir(string $directory, string $needle, string $replacement): void {
    $files = static::scandirRecursive($directory, static::ignoredPaths());
    foreach ($files as $filename) {
      static::replaceContent($filename, $needle, $replacement);
    }
  }

  public static function replaceContent(string $file, string $needle, string $replacement): void {
    if (!is_readable($file) || static::isExcluded($file)) {
      return;
    }

    $content = (new Filesystem())->readFile($file);
    if ($content === '' || $content === '0') {
      return;
    }

    if (static::isRegex($needle)) {
      $replaced = preg_replace($needle, $replacement, $content);
    }
    else {
      $replaced = str_replace($needle, $replacement, $content);
    }
    if ($replaced != $content) {
      static::dump($file, $replaced);
    }
  }

  public static function removeLine(string $file, string $needle): void {
    if (!is_readable($file) || static::isExcluded($file)) {
      return;
    }

    $content = (new Filesystem())->readFile($file);

    $line_ending = "\n";
    if (str_contains($content, "\r\n")) {
      $line_ending = "\r\n";
    }
    elseif (str_contains($content, "\r")) {
      $line_ending = "\r";
    }

    $lines = preg_split("/\r\n|\r|\n/", $content);
    if ($lines === FALSE) {
      // @codeCoverageIgnoreStart
      return;
      // @codeCoverageIgnoreEnd
    }

    $lines = array_filter($lines, fn($line): bool => !str_contains($line, $needle));

    $content = implode($line_ending, $lines);

    static::dump($file, $content);
  }

  public static function removeToken(string $file, string $token_begin, ?string $token_end = NULL, bool $with_content = FALSE): void {
    if (static::isExcluded($file)) {
      return;
    }

    if (!is_readable($file)) {
      return;
    }

    $token_end = $token_end ?? $token_begin;

    $content = file_get_contents($file);
    if (!$content) {
      return;
    }

    if ($token_begin !== $token_end) {
      $token_begin_count = preg_match_all('/' . preg_quote($token_begin) . '/', $content);
      $token_end_count = preg_match_all('/' . preg_quote($token_end) . '/', $content);
      if ($token_begin_count !== $token_end_count) {
        throw new \RuntimeException(sprintf('Invalid begin and end token count in file %s: begin is %s(%s), end is %s(%s).', $file, $token_begin, $token_begin_count, $token_end, $token_end_count));
      }
    }

    $out = [];
    $within_token = FALSE;

    $lines = file($file);
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

    file_put_contents($file, implode('', $out));
  }

  public static function removeTokenInDir(string $directory, ?string $token = NULL): void {
    $token_start = '#;';
    $token_end = '#;';
    $with_content = FALSE;

    if (!is_null($token)) {
      $token_start = '#;< ' . $token;
      $token_end = '#;> ' . $token;
      $with_content = TRUE;
    }

    $files = static::scandirRecursive($directory, static::ignoredPaths());
    foreach ($files as $filename) {
      static::removeToken($filename, $token_start, $token_end, $with_content);
    }
  }

  public static function isRegex(string $string): bool {
    if ($string === '' || strlen($string) < 3) {
      return FALSE;
    }

    // Extract the first character as the delimiter.
    $delimiter = $string[0];

    if (!in_array($delimiter, ['/', '#', '~'])) {
      return FALSE;
    }

    $last_char = substr($string, -1);
    $before_last_char = substr($string, -2, 1);
    if (
      ($last_char !== $delimiter && !in_array($last_char, ['i', 'm', 's']))
      || ($before_last_char !== $delimiter && in_array($before_last_char, ['i', 'm', 's']))
    ) {
      return FALSE;
    }

    // Test the regex.
    $result = preg_match($string, '');
    return $result !== FALSE && preg_last_error() === PREG_NO_ERROR;
  }

  /**
   * Get list of paths to ignore.
   *
   * @return array<int, string>
   *   Array of paths to ignore.
   */
  public static function ignoredPaths(): array {
    return array_merge([
      '/.git/',
      '/.idea/',
      '/vendor/',
      '/node_modules/',
      '/.data/',
    ], static::internalPaths());
  }

  protected static function isPathMatchesPattern(string $path, string $pattern): bool {
    // Match directory pattern (e.g., "dir/").
    if (str_ends_with($pattern, DIRECTORY_SEPARATOR)) {
      return str_starts_with($path, $pattern);
    }

    // Match direct children (e.g., "dir/*").
    if (str_contains($pattern, '/*')) {
      $parent_dir = rtrim($pattern, '/*') . DIRECTORY_SEPARATOR;

      return str_starts_with($path, $parent_dir) && substr_count($path, DIRECTORY_SEPARATOR) === substr_count($parent_dir, DIRECTORY_SEPARATOR);
    }

    // @phpcs:ignore Drupal.Functions.DiscouragedFunctions.Discouraged
    return fnmatch($pattern, $path);
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

  /**
   * Check if file is excluded from processing.
   *
   * @param string $file
   *   Filename to check.
   *
   * @return bool
   *   TRUE if file is excluded, FALSE otherwise.
   */
  protected static function isExcluded(string $file): bool {
    $excluded_patterns = [
      '.+\.png',
      '.+\.jpg',
      '.+\.jpeg',
      '.+\.bpm',
      '.+\.tiff',
    ];

    return (bool) preg_match('/^(' . implode('|', $excluded_patterns) . ')$/', $file);
  }

}
