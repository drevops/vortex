<?php

namespace Drupal\drupal_helpers;

/**
 * Class Entity.
 *
 * @package Drupal\drupal_helpers
 */
class Entity {

  /**
   * Get label for entity bundle.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle name.
   *
   * @return string
   *   Label for bundle if label defined or provided bundle name.
   */
  public static function getBundleLabel($entity_type, $bundle) {
    $labels = &drupal_static(__FUNCTION__, []);

    if (empty($labels)) {
      foreach (entity_get_info() as $entity_info_type => $entity_info) {
        foreach ($entity_info['bundles'] as $entity_info_bundle => $entity_info_bundle_info) {
          $labels[$entity_info_type][$entity_info_bundle] = !empty($entity_info_bundle_info['label']) ? $entity_info_bundle_info['label'] : $bundle;
        }
      }
    }

    return $labels[$entity_type][$bundle];
  }

}
