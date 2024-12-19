<?php

declare(strict_types=1);

namespace DrevOps\Installer\Traits;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Trait FilesystemTrait.
 */
trait FilesystemTrait {

  /**
   * Current directory where call originated.
   */
  protected string $fsRootDir;

  /**
   * File system for custom commands.
   */
  protected Filesystem $fs;

  /**
   * Stack of original current working directories.
   *
   * This is used throughout commands to track working directories.
   * Usually, each command would call setCwd() in the beginning and
   * restoreCwd() at the end of the run.
   *
   * @var array<string>
   */
  protected array $fsOriginalCwdStack = [];

  /**
   * Set root directory path.
   *
   * @param string|null $path
   *   The path of the root directory.
   *
   * @return static
   *   The called object.
   */
  protected function fsSetRootDir(?string $path = NULL): static {
    $path = empty($path) ? $this->fsGetRootDir() : $this->fsGetAbsolutePath($path);
    $this->fsAssertPathsExist($path);
    $this->fsRootDir = $path;

    return $this;
  }

  /**
   * Get root directory.
   *
   * @return string
   *   Get value of the root directory, the directory where the
   *   script was started from or current working directory.
   */
  protected function fsGetRootDir(): string {
    if (isset($this->fsRootDir)) {
      return $this->fsRootDir;
    }

    if (isset($_SERVER['PWD'])) {
      return $_SERVER['PWD'];
    }

    return (string) getcwd();
  }

  /**
   * Set current working directory.
   *
   * It is important to note that this should be called in pair with
   * cwdRestore().
   *
   * @param string $dir
   *   Path to the current directory.
   *
   * @return static
   *   The called object.
   */
  protected function fsSetCwd(string $dir): static {
    chdir($dir);
    $this->fsOriginalCwdStack[] = $dir;

    return $this;
  }

  /**
   * Set current working directory to a previously saved path.
   *
   * It is important to note that this should be called in pair with cwdSet().
   */
  protected function fsCwdRestore(): void {
    $dir = array_shift($this->fsOriginalCwdStack);
    if ($dir) {
      chdir($dir);
    }
  }

  /**
   * Get current working directory.
   *
   * @return string
   *   Full path of current working directory.
   */
  protected function fsCwdGet(): string {
    return (string) getcwd();
  }

  /**
   * Get absolute path for provided file.
   *
   * @param string $file
   *   File to resolve. If absolute, no resolution will be performed.
   * @param string|null $root
   *   Optional path to root dir. If not provided, internal root path is used.
   *
   * @return string
   *   Absolute path for provided file.
   */
  protected function fsGetAbsolutePath(string $file, ?string $root = NULL): string {
    if ($this->fs->isAbsolutePath($file)) {
      return $this->fsRealpath($file);
    }

    $root = $root ? $root : $this->fsGetRootDir();
    $root = $this->fsRealpath($root);
    $file = $root . DIRECTORY_SEPARATOR . $file;

    return $this->fsRealpath($file);
  }

  /**
   * Check that path exists.
   *
   * @param string|array<string> $paths
   *   File name or array of file names to check.
   * @param bool $strict
   *   If TRUE and the file does not exist, an exception will be thrown.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE if file exists and FALSE if not, but only if $strict is FALSE.
   *
   * @throws \Exception
   *   If at least one file does not exist.
   */
  protected function fsAssertPathsExist($paths, bool $strict = TRUE): bool {
    $paths = is_array($paths) ? $paths : [$paths];

    if (!$this->fs->exists($paths)) {
      if ($strict) {
        throw new \Exception(sprintf('One of the files or directories does not exist: %s', implode(', ', $paths)));
      }

      return FALSE;
    }

    return TRUE;
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
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  protected function fsRealpath(string $path): string {
    // Whether $path is unix or not.
    $unipath = $path === '' || $path[0] !== '/';
    $unc = str_starts_with($path, '\\\\');

    // Attempt to detect if path is relative in which case, add cwd.
    if (!str_contains($path, ':') && $unipath && !$unc) {
      $path = getcwd() . DIRECTORY_SEPARATOR . $path;
      if ($path[0] === '/') {
        $unipath = FALSE;
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

    // Resolve any symlinks.
    if (function_exists('readlink') && file_exists($path) && linkinfo($path) > 0) {
      $path = readlink($path);

      if (!$path) {
        throw new \Exception(sprintf('Could not resolve symlink for path: %s', $path));
      }
    }

    // Put initial separator that could have been lost.
    $path = $unipath ? $path : '/' . $path;

    $path = $unc ? '\\\\' . $path : $path;

    if (str_starts_with($path, sys_get_temp_dir())) {
      $tmp_realpath = realpath(sys_get_temp_dir());
      if ($tmp_realpath) {
        $path = str_replace(sys_get_temp_dir(), $tmp_realpath, $path);
      }
    }

    return $path;
  }

}
