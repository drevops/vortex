<?php

namespace DrevOps\Installer\Utils;

/**
 * Array utilities.
 */
class Arrays {

  /**
   * Sort array by the given keys, preserving original array's keys.
   *
   * @param array $array
   *   The array to sort.
   * @param array $keys
   *   The keys to sort by.
   *
   * @return array
   *   The sorted array.
   */
  public static function sortByKeyArray(array $array, array $keys): array {
    $result = [];
    $remaining = [];

    foreach ($keys as $key) {
      if (isset($array[$key])) {
        if (is_numeric($key)) {
          $result[] = $array[$key];
        }
        else {
          $result[$key] = $array[$key];
        }
        unset($array[$key]);
      }
    }

    foreach ($array as $key => $value) {
      if (is_numeric($key)) {
        $remaining[] = $value;
      }
      else {
        $result[$key] = $value;
      }
    }

    return array_merge($result, $remaining);
  }

  /**
   * Sort array by the given values, preserving original array's non-numeric keys.
   *
   * @param array $array
   *   The array to sort.
   * @param array $values
   *   The values to sort by.
   *
   * @return array
   *   The sorted array.
   */
  public static function sortByValueArray(array $array, array $values): array {
    $sorted = $array;

    uasort($sorted, static function ($a, $b) use ($values): int {
      $a_index = array_search($a, $values, TRUE);
      $b_index = array_search($b, $values, TRUE);

      return ($a_index === FALSE ? PHP_INT_MAX : $a_index) <=> ($b_index === FALSE ? PHP_INT_MAX : $b_index);
    });

    // Preserve non-numeric keys and reset numeric ones.
    $result = [];
    foreach ($sorted as $key => $value) {
      if (is_numeric($key)) {
        $result[] = $value;
      }
      else {
        $result[$key] = $value;
      }
    }

    return $result;
  }

  /**
   * Reindex array.
   *
   * @param array $values
   *   The array to reindex.
   * @param int $start
   *   The start index.
   *
   * @return array
   *   The reindexed array.
   */
  public static function reindex(array $values, $start): array {
    return empty($values)
      ? []
      : array_combine(range($start, count($values) + $start - 1), $values);
  }

}
