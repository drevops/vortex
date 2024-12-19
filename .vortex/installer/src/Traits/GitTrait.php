<?php

declare(strict_types=1);

namespace DrevOps\Installer\Traits;

/**
 * Git trait.
 */
trait GitTrait {

  protected function gitFileIsTracked(string $path, string $dir): bool {
    if (is_dir($dir . DIRECTORY_SEPARATOR . '.git')) {
      $cwd = getcwd();
      if (!$cwd) {
        throw new \RuntimeException('Unable to determine current working directory.');
      }

      chdir($dir);
      $this->doExec(sprintf('git ls-files --error-unmatch "%s" 2>&1 >/dev/null', $path), $output, $code);
      chdir($cwd);

      return $code === 0;
    }

    return FALSE;
  }

}
