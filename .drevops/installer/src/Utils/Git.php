<?php

namespace DrevOps\Installer\Utils;

class Git {

  public static function fileIsTracked($path, $dir) {
    if (is_dir($dir . DIRECTORY_SEPARATOR . '.git')) {
      $cwd = getcwd();
      chdir($dir);
      Executor::doExec("git ls-files --error-unmatch \"{$path}\" 2>&1 >/dev/null", $output, $code);
      chdir($cwd);

      return $code === 0;
    }

    return FALSE;
  }

}
