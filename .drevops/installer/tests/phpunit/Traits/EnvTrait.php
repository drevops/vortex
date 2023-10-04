<?php

namespace Drevops\Installer\Tests\Traits;

trait EnvTrait {

  protected static $env = [];

  public static function envSet($name, $value) {
    static::$env[$name] = $value;
    putenv($name . '=' . $value);
  }

  public static function envUnset($name) {
    unset(static::$env[$name]);
    putenv($name);
  }

  public static function envGet($name) {
    return getenv($name);
  }

  public static function envIsSet($name) {
    return getenv($name) !== FALSE;
  }

  public static function envIsUnset($name) {
    return getenv($name) === FALSE;
  }

  public static function envReset() {
    foreach (static::$env as $name => $value) {
      static::envUnset($name);
    }
    static::$env = [];
  }

  public static function envFromInput(array &$input, $prefix, $remove = TRUE) {
    foreach ($input as $name => $value) {
      if (str_starts_with($name, $prefix)) {
        static::envSet($name, $value);
        if ($remove) {
          unset($input[$name]);
        }
      }
    }
  }

}
