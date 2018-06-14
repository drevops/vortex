<?php

namespace Drupal\drupal_helpers;

/**
 * Class Form.
 *
 * @package Drupal\drupal_helpers
 */
class Form {

  /**
   * Get default values from the form.
   *
   * @param ...
   *   Variable number of arguments:
   *   - $params: Array of parameter values. Usually, taken from
   *   drupal_get_query_parameters().
   *   - ...: Variable number of keys to traverse $params.
   *   - $default: Default value to return if parameter does not exist.
   *
   * @return mixed|null
   *   Returns one of the following values:
   *   - value: Parameter value if all provided keys exist.
   *   - default value: Value of $default if one or more keys do not exist in
   *     $params.
   *   - NULL: If this function was called with insufficient number of
   *     arguments.
   */
  public static function formGetDefaults() {
    $args = func_get_args();

    if (count($args) < 3) {
      return NULL;
    }

    $params = array_shift($args);
    $defaults = array_pop($args);
    $keys = $args;

    $val = $params;
    foreach ($keys as $key) {
      if (isset($val[$key])) {
        $val = $val[$key];
      }
      else {
        $val = $defaults;
        break;
      }
    }

    return $val;
  }

}
