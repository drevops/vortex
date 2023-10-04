<?php

namespace DrevOps\Installer\Utils;

use DrevOps\Installer\Command\Installer;
use RuntimeException;

class Executor {

  /**
   * Execute command wrapper.
   *
   * @param $command
   * @param array|null $output
   * @param null $return_var
   *
   * @return false|string
   */
  public static function doExec($command, array &$output = NULL, &$return_var = NULL) {
    $result = exec($command, $output, $return_var);

    return $result;
  }

  public static function commandExists($command) {
    static::doExec("command -v $command", $lines, $ret);
    if ($ret === 1) {
      throw new RuntimeException(sprintf('Command "%s" does not exist in the current environment.', $command));
    }
  }

}
