<?php

namespace DrevOps\Installer\Utils;

class Env {

  /**
   * Reliable wrapper to work with environment values.
   */
  public static function get(string $name, mixed $default = NULL): mixed {
    $vars = getenv();

    if (!isset($vars[$name]) || $vars[$name] === '') {
      return $default;
    }

    return $vars[$name];
  }
}
