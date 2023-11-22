<?php

namespace DrevOps\DevTool\Utils;

/**
 * Class Downloader.
 *
 * Utility to work with arrays.
 *
 * @package DrevOps\DevTool\Utils
 */
class Arrays {

  /**
   * Insert a new key/value before a specific key in an associative array.
   *
   * @param array $array
   *   The array to insert into.
   * @param string $key
   *   The key to insert before.
   * @param string $new_key
   *   The key to insert.
   * @param mixed $new_value
   *   The value to insert.
   *
   * @return array
   *   The new array if the key exists, otherwise the original array.
   */
  public static function insertAfterKey(array $array, string $key, string $new_key, mixed $new_value): array {
    $newArray = [];
    $inserted = FALSE;

    foreach ($array as $currentKey => $value) {
      $newArray[$currentKey] = $value;

      if ($currentKey === $key) {
        $newArray[$new_key] = $new_value;
        $inserted = TRUE;
      }
    }

    if (!$inserted) {
      $newArray[$new_key] = $new_value;
    }

    return $newArray;
  }

  /**
   * Get a value from an array using a string of dot-separated keys.
   *
   * @param array $array
   *   The array from which to get the value.
   * @param array|string $parents
   *   An array of parent keys of the value, starting with the outermost key.
   * @param null $key_exists
   *   (optional) If given, an already defined variable that is altered by
   *    reference.
   *
   * @return mixed
   *   The value specified by the parents or NULL if the parents are not found.
   */
  public static function &getValue(array &$array, array|string $parents, &$key_exists = NULL): mixed {
    $ref = &$array;

    $parents = is_array($parents) ? $parents : explode('.', $parents);

    foreach ($parents as $parent) {
      if (is_array($ref) && \array_key_exists($parent, $ref)) {
        $ref = &$ref[$parent];
      }
      else {
        $key_exists = FALSE;
        $null = NULL;

        return $null;
      }
    }
    $key_exists = TRUE;

    return $ref;
  }

  /**
   * Set a value in an array using a string of dot-separated keys.
   *
   * @param array $array
   *   The array in which to set the value.
   * @param array|string $parents
   *   An array of parent keys of the value, starting with the outermost key.
   * @param mixed $value
   *   The value to set.
   * @param bool $force
   *   (optional) If TRUE, any non-array parents will be converted to an array.
   */
  public static function setValue(array &$array, array|string $parents, mixed $value, bool $force = FALSE): void {
    $ref = &$array;

    $parents = is_array($parents) ? $parents : explode('.', $parents);

    foreach ($parents as $parent) {
      // PHP auto-creates container arrays and NULL entries without error if
      // $ref is NULL, but throws an error if $ref is set, but not an array.
      if ($force && isset($ref) && !is_array($ref)) {
        $ref = [];
      }
      $ref = &$ref[$parent];
    }
    $ref = $value;
  }

  /**
   * Merges multiple arrays, recursively, and returns the merged array.
   *
   * This function is similar to PHP's array_merge_recursive() function, but it
   * handles non-array values differently. When merging values that are not both
   * arrays, the latter value replaces the former rather than merging with it.
   *
   * Example:
   *
   * @code
   *
   * $link_options_1 = [
   *   'fragment' => 'x',
   *   'attributes' => ['title' => t('X'), 'class' => ['a', 'b']],
   * ];
   * $link_options_2 = [
   *   'fragment' => 'y',
   *   'attributes' => ['title' => t('Y'), 'class' => ['c', 'd']],
   * ];
   *
   * // This results in
   * [
   *   'fragment' => ['x', 'y'],
   *   'attributes' => [
   *      'title' => [t('X'), t('Y')],
   *      'class' => ['a', 'b', 'c', 'd']
   *   ]
   * ]
   * $incorrect = array_merge_recursive($link_options_1, $link_options_2);
   *
   * // This results in
   * [
   *   'fragment' => 'y',
   *   'attributes' => [
   *     'title' => t('Y'),
   *     'class' => ['a', 'b', 'c', 'd']
   *   ]
   * ]
   * $correct = Arrays::mergeDeep($link_options_1, $link_options_2);
   * @endcode
   *
   * @param array ...
   *   Arrays to merge.
   *
   * @return array
   *   The merged array.
   *
   * @see Arrays::mergeDeepArray()
   * @phpstan-ignore-next-line
   */
  public static function mergeDeep(): array {
    return self::mergeDeepArray(func_get_args());
  }

  /**
   * Merges multiple arrays, recursively, and returns the merged array.
   *
   * This function is equivalent to Arrays::mergeDeep(), except the
   * input arrays are passed as a single array parameter rather than a variable
   * parameter list.
   *
   * The following are equivalent:
   * - Arrays::mergeDeep($a, $b);
   * - Arrays::mergeDeepArray(array($a, $b));
   *
   * The following are also equivalent:
   * - call_user_func_array('Arrays::mergeDeep', $arrays_to_merge);
   * - Arrays::mergeDeepArray($arrays_to_merge);
   *
   * @param array $arrays
   *   An arrays of arrays to merge.
   * @param bool $preserve_integer_keys
   *   (optional) If given, integer keys will be preserved and merged instead of
   *   appended. Defaults to FALSE.
   *
   * @return array
   *   The merged array.
   *
   * @see Arrays::mergeDeep()
   */
  public static function mergeDeepArray(array $arrays, bool $preserve_integer_keys = FALSE): array {
    $result = [];
    foreach ($arrays as $array) {
      foreach ($array as $key => $value) {
        // Renumber integer keys as array_merge_recursive() does unless
        // $preserve_integer_keys is set to TRUE. Note that PHP automatically
        // converts array keys that are integer strings (e.g., '1') to integers.
        if (is_int($key) && !$preserve_integer_keys) {
          $result[] = $value;
        }
        // Recurse when both values are arrays.
        elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
          $result[$key] = self::mergeDeepArray([$result[$key], $value], $preserve_integer_keys);
        }
        // Otherwise, use the latter value, overriding any previous value.
        else {
          $result[$key] = $value;
        }
      }
    }

    return $result;
  }

}
