<?php

namespace DrevOps\Installer\Utils;

/**
 * Constants loader.
 *
 * Loads constants from a class.
 */
class ConstantsLoader {

  /**
   * Load constants from a class.
   *
   * @param object|string $class
   *   The class name or object.
   * @param string|null $prefix
   *   The prefix to filter constants by.
   * @param bool $prefix_is_key
   *   Whether the prefix is a key or a value.
   *
   * @return array
   *   The constants.
   */
  public static function load(object|string $class, string|null $prefix = NULL, $prefix_is_key = TRUE): array {
    $reflection = new \ReflectionClass($class);
    $constants = $reflection->getConstants();

    if ($prefix) {
      $constants = array_filter($constants, static function ($value, $key) use ($prefix, $prefix_is_key): bool {
        return str_starts_with($prefix_is_key ? $key : $value, $prefix);
      }, ARRAY_FILTER_USE_BOTH);
    }

    asort($constants);

    return $constants;
  }

}
