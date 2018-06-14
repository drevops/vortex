<?php

namespace Drupal\drupal_helpers;

/**
 * Class Feature.
 *
 * @package Drupal\drupal_helpers
 */
class Feature extends Module {

  /**
   * Reverts a feature.
   *
   * @param string $module
   *   Machine name of the feature to revert.
   * @param string $component
   *   Name of an individual component to revert. Defaults to empty component
   *   name to trigger all components revert.
   */
  public static function revert($module, $component = '') {
    module_load_include('inc', 'features', 'features.export');
    features_include();

    if (($feature = feature_load($module, TRUE)) && module_exists($module)) {
      $components = [];
      if (empty($component)) {
        // Forcefully revert all components of a feature.
        foreach (array_keys($feature->info['features']) as $component) {
          if (features_hook($component, 'features_revert')) {
            $components[] = $component;
          }
        }
      }
      else {
        // Revert only specified component.
        $components[] = $component;
      }

      foreach ($components as $component) {
        features_revert([$module => [$component]]);
      }

      General::messageSet(t('Reverted "!module" feature components !components.', [
        '!module' => $module,
        '!components' => implode(', ', $components),
      ]));
    }
    else {
      General::messageSet(t('Unable to revert "!module" feature.', ['!module' => $module]));
    }
  }

}
