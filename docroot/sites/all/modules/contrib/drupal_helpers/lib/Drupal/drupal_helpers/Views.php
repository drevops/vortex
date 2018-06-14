<?php

namespace Drupal\drupal_helpers;

/**
 * Class Views.
 *
 * @package Drupal\drupal_helpers
 */
class Views {

  /**
   * Enable a view.
   *
   * @param string $name
   *   View machine name.
   * @param bool $reset_cache
   *   Optional flag to reset views cache after enabling a view. Defaults
   *   to TRUE.
   */
  public static function enable($name, $reset_cache = TRUE) {
    $defaults = variable_get('views_defaults', []);
    if (isset($defaults[$name])) {
      unset($defaults[$name]);

      variable_set('views_defaults', $defaults);

      if ($reset_cache && function_exists('views_invalidate_cache')) {
        views_invalidate_cache();
      }
    }
  }

  /**
   * Disable a view.
   *
   * @param string $name
   *   View machine name.
   * @param bool $reset_cache
   *   Optional flag to reset views cache after disabling a view. Defaults
   *   to TRUE.
   */
  public static function disable($name, $reset_cache = TRUE) {
    $defaults = variable_get('views_defaults', []);
    $defaults[$name] = TRUE;

    variable_set('views_defaults', $defaults);

    if ($reset_cache && function_exists('views_invalidate_cache')) {
      views_invalidate_cache();
    }
  }

}
