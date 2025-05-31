<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_base\Traits;

/**
 * Trait ArrayTrait.
 *
 * Provides methods for working with arrays.
 *
 * @codeCoverageIgnore
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
      $array[$k] = is_array($item) ? static::arrayReplaceValue($item, $cb) : $cb($item);
    }

    return $array;
  }

}
