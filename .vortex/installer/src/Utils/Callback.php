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
}
