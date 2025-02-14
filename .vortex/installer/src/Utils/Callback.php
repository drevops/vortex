<?php

namespace DrevOps\Installer\Utils;

class Callback {

  /**
   * Execute this class's callback.
   *
   * @param string $prefix
   *   Prefix of the callback.
   * @param string $name
   *   Name of the callback.
   *
   * @return mixed
   *   Result of the callback.
   */
  public static function execute(string $prefix, string $name): mixed {
    $args = func_get_args();
    $args = array_slice($args, 2);

    $callback = [static::class, Converter::phpMethod($prefix . '_' . $name)];
    if (method_exists($callback[0], $callback[1]) && is_callable($callback)) {
      return call_user_func_array($callback, $args);
    }

    return NULL;
  }

  /**
   * Execute command.
   *
   * @param string $command
   *   Command to execute.
   * @param array<int, string>|null $output
   *   Output of the command.
   * @param int|null $return_var
   *   Return code of the command.
   *
   * @return string|false
   *   Result of the command.
   */
  public static function doExec(string $command, ?array &$output = NULL, ?int &$return_var = NULL): string|false {
    //    if ($this->config->isInstallDebug()) {
    //      $this->status(sprintf('COMMAND: %s', $command), self::INSTALLER_STATUS_DEBUG);
    //    }

    $result = exec($command, $output, $return_var);

    //    if ($this->config->isInstallDebug()) {
    //      $this->status(sprintf('  OUTPUT: %s', implode('', $output)), self::INSTALLER_STATUS_DEBUG);
    //      $this->status(sprintf('  CODE  : %s', $return_var), self::INSTALLER_STATUS_DEBUG);
    //      $this->status(sprintf('  RESULT: %s', $result), self::INSTALLER_STATUS_DEBUG);
    //    }

    return $result;
  }
}
