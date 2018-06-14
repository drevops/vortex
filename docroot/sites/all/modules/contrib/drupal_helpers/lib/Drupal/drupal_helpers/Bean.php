<?php

namespace Drupal\drupal_helpers;

if (!module_exists('bean')) {
  throw new \Exception('BEAN module is not present.');
}

/**
 * Class Bean.
 *
 * @package Drupal\drupal_helpers
 */
class Bean {

  /**
   * Load or create bean.
   *
   * @param string $type
   *   Bean type.
   * @param string $label
   *   Bean label.
   * @param null|string $delta
   *   Bean delta.
   *
   * @return mixed
   *   Loaded or created bean object.
   */
  public static function loadOrCreate($type, $label, $delta = NULL) {
    $delta = (!is_null($delta)) ? $delta : strtolower(substr(preg_replace('/[^A-Za-z0-9]/', '-', $label), 0, 32));
    $bean_data = [
      'type' => $type,
      'label' => $label,
      'delta' => $delta,
      'title' => '',
    ];

    // Load or create bean.
    $bean = bean_delta_load($bean_data['delta']);
    if (!$bean) {
      // Create bean.
      $bean = bean_create($bean_data);
    }

    return $bean;
  }

}
