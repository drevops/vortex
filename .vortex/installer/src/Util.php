<?php

namespace DrevOps\Installer;

class Util {

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
  public static function executeCallback(string $prefix, string $name): mixed {
    $args = func_get_args();
    $args = array_slice($args, 2);

    $name = Converter::snake2pascal($name);

    $callback = [static::class, $prefix . $name];
    if (method_exists($callback[0], $callback[1]) && is_callable($callback)) {
      return call_user_func_array($callback, $args);
    }

    return NULL;
  }

  /**
   * Get the value of a composer.json key.
   *
   * @param string $name
   *   Name of the key.
   * @param string $composer_json
   *
   * @return mixed|null
   *   Value of the key or NULL if not found.
   */
  public static function getComposerJsonValue(string $name, string $composer_json): mixed {
    if (is_readable($composer_json)) {
      $contents = file_get_contents($composer_json);
      if ($contents === FALSE) {
        return NULL;
      }

      $json = json_decode($contents, TRUE);
      if (isset($json[$name])) {
        return $json[$name];
      }
    }

    return NULL;
  }
}
