<?php

namespace DrevOps\DevTool\Tests\Traits;

/**
 * Trait ArrayTrait.
 *
 * Provides methods for working with arrays.
 */
trait ArrayTrait {

  /**
   * Recursively replace a value in the array using provided callback.
   *
   * @param array $array
   *   The array to process.
   * @param callable $cb
   *   The callback to use.
   *
   * @return array
   *   The processed array.
   */
  public static function arrayReplaceValue(array $array, callable $cb): array {
    foreach ($array as $k => $item) {
      if (is_array($item)) {
        $array[$k] = static::arrayReplaceValue($item, $cb);
      }
      else {
        $array[$k] = $cb($item);
      }
    }

    return $array;
  }

}
