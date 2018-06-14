<?php

namespace Drupal\drupal_helpers;

/**
 * Class Variable.
 *
 * @package Drupal\drupal_helpers
 */
class Variable {

  /**
   * Set value for multiple variables.
   *
   * @param string|array $names
   *   Name or array of variables as exact or wildcard match.
   * @param mixed $value
   *   Value to set.
   */
  public static function setValues($names, $value) {
    $all_names = self::extractNames($names);

    foreach ($all_names as $name) {
      variable_set($name, $value);
    }
  }

  /**
   * Get variables by name.
   *
   * @param string|array $names
   *   Name or array of variables as exact or wildcard match.
   *
   * @return array
   *   Array of variables, keyed by name.
   */
  public static function getValues($names) {
    $all_names = self::extractNames($names);

    $all_values = [];
    foreach ($all_names as $name) {
      $all_values[$name] = variable_get($name);
    }

    return $all_values;
  }

  /**
   * Store variables in temporary storage in DB.
   *
   * @param string|array $names
   *   Name or array of variables as exact or wildcard match.
   * @param null|string $storage_key
   *   Optional storage key for variable storage. Defaults to first variable
   *   name.
   */
  public static function store($names, $storage_key = NULL) {
    $storage_key = $storage_key ? $storage_key : (is_array($names) ? reset($names) : $names);

    $existing_values = self::getValues($names);
    $storage_key = 'drupal_helpers_storage_' . $storage_key;
    variable_set($storage_key, $existing_values);
  }

  /**
   * Restore variables from temporary storage in DB.
   *
   * @param null|string $storage_key
   *   Storage key to retrieve variables from.
   */
  public static function restore($storage_key) {
    $storage_key = 'drupal_helpers_storage_' . $storage_key;

    $storage_values = variable_get($storage_key, []);
    foreach ($storage_values as $name => $value) {
      if (is_null($value)) {
        variable_del($name);
      }
      else {
        variable_set($name, $value);
      }
    }

    variable_del($storage_key);
  }

  /**
   * Helper to extract variable names from provided name or array of names.
   *
   * @param string|array $names
   *   Name or array of variables as exact or wildcard match.
   * @param array $variables
   *   Optional array of variables. If not provided, global $conf will be used
   *   instead.
   *
   * @return array
   *   Array of existing variable names.
   */
  protected static function extractNames($names, array $variables = NULL) {
    $names = is_array($names) ? $names : (!empty($names) ? [$names] : []);

    $variables = $variables ? $variables : static::getAll();

    $all_names = [];
    foreach ($names as $name) {
      $all_names = array_merge($all_names, static::getNameWildcard($name, $variables));
    }

    // If names where not found in DB - the variables have not been set, but
    // we are still expecting provided names to be returned.
    $all_names = !empty($all_names) ? $all_names : $names;

    return $all_names;
  }

  /**
   * Get variable name form a single string.
   *
   * @param string $pattern
   *   Variable name that may represent regex, wildcard or exact variable name.
   * @param array $variables
   *   Optional array of variables. If not provided, global $conf will be used
   *   instead.
   *
   * @return array
   *   Array of variable names that match provided variable name pattern.
   */
  protected static function getNameWildcard($pattern, array $variables) {
    // If string is not a regexp - turn it to one.
    if (!preg_match('/^\/[\s\S]+\/$/', $pattern)) {
      $pattern = trim($pattern, '/');
      $pattern = str_replace('*', '.*?', $pattern);
      $pattern = '/^' . $pattern . '$/';
    }

    return array_keys(array_intersect_key($variables, array_flip(preg_grep($pattern, array_keys($variables)))));
  }

  /**
   * Helper to get all variables from global $conf.
   */
  protected static function getAll() {
    global $conf;

    return $conf;
  }

}
