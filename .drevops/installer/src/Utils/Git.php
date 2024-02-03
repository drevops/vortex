<?php

namespace DrevOps\Installer\Utils;

/**
 * Git.
 *
 * Git utilities.
 */
class Git {

  /**
   * Check if a file is tracked by git.
   *
   * @param string $path
   *   The path.
   * @param string $dir
   *   The directory.
   *
   * @return bool
   *   Whether the file is tracked by git.
   */
  public static function fileIsTracked($path, string $dir): bool {
    if (is_dir($dir . DIRECTORY_SEPARATOR . '.git')) {
      $cwd = getcwd();
      chdir($dir);
      Executor::doExec(sprintf('git ls-files --error-unmatch "%s" 2>&1 >/dev/null', $path), $output, $code);
      chdir($cwd);

      return $code === 0;
    }

    return FALSE;
  }

}
