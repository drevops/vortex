<?php

namespace Drupal\xautoload\DrupalSystem;

class DrupalSystem implements DrupalSystemInterface {

  function __construct() {
    if (!function_exists('drupal_get_filename')) {
      debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      echo "\n\n";
      throw new \Exception("This class works only within a working Drupal environment.");
    }
  }

  /**
   * {@inheritdoc}
   */
  function variableGet($name, $default = NULL) {
    return variable_get($name, $default);
  }

  /**
   * {@inheritdoc}
   */
  function drupalGetFilename($type, $name) {
    return DRUPAL_ROOT . '/' . drupal_get_filename($type, $name);
  }

  /**
   * {@inheritdoc}
   */
  function getExtensionTypes($extension_names) {
    $q = db_select('system');
    $q->condition('name', $extension_names);
    $q->fields('system', array('name', 'type'));

    return $q->execute()->fetchAllKeyed();
  }

  /**
   * {@inheritdoc}
   */
  function getActiveExtensions() {
    // Doing this directly tends to be a lot faster than system_list().
    return db_query(
      "SELECT name, type from {system} WHERE status = 1"
    )->fetchAllKeyed();
  }
}
