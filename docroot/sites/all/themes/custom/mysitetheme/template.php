<?php

/**
 * @file
 * Template for MYSITE theme.
 */

/**
 * Implements hook_css_alter().
 */
function mysitetheme_css_alter(&$css) {
  if (variable_get('livereload', FALSE)) {
    // Alter css to display as link tags.
    foreach ($css as $key => $value) {
      $css[$key]['preprocess'] = FALSE;
    }
  }
}

/**
 * Implements hook_js_alter().
 */
function mysitetheme_js_alter(&$javascript) {
  // Add Livereload support.
  if (variable_get('livereload', FALSE)) {
    $path = 'http://localhost:35729/livereload.js?snipver=1';
    drupal_add_js($path, 'external');
  }
}
