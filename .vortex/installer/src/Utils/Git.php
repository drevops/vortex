<?php

namespace DrevOps\Installer\Utils;

class Git {

  public static function fileIsTracked(string $path, string $dir): bool {
    if (is_dir($dir . DIRECTORY_SEPARATOR . '.git')) {
      $cwd = getcwd();
      if (!$cwd) {
        throw new \RuntimeException('Unable to determine current working directory.');
      }

      chdir($dir);
      Callback::doExec(sprintf('git ls-files --error-unmatch "%s" 2>&1 >/dev/null', $path), $output, $code);
      chdir($cwd);

      return $code === 0;
    }

    return FALSE;
  }

}
