<?php

namespace Drevops\Installer\Tests\Traits;

/**
 *
 */
trait EnvTrait {

  protected static $env = [];

  public static function envSet(string $name, string $value): void {
    static::$env[$name] = $value;
    putenv($name . '=' . $value);
  }

  public static function envUnset($name): void {
    unset(static::$env[$name]);
    putenv($name);
  }

  public static function envGet($name): string|false {
    return getenv($name);
  }

  public static function envIsSet($name): bool {
    return getenv($name) !== FALSE;
  }

  public static function envIsUnset($name): bool {
    return getenv($name) === FALSE;
  }

  public static function envReset(): void {
    foreach (static::$env as $name => $value) {
      static::envUnset($name);
    }
    static::$env = [];
  }

  public static function envFromInput(array &$input, $prefix, $remove = TRUE): void {
    foreach ($input as $name => $value) {
      if (str_starts_with($name, (string) $prefix)) {
        static::envSet($name, $value);
        if ($remove) {
          unset($input[$name]);
        }
      }
    }
  }

}
