<?php

namespace DrevOps\Installer\Utils;

/**
 *
 */
class Git {

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
