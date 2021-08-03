<?php

/**
 * @file
 * Template for YOURSITE theme.
 */

/**
 * Implements template_preprocess_html().
 */
function your_site_preprocess_html(&$variables) {
  _your_site_preprocess_admin_toolbar($variables);
}

/**
 * Preprocess admin toolbar.
 */
function _your_site_preprocess_admin_toolbar(&$variables) {
  global $conf;
  // Hide admin toolbar.
  if (!empty($conf['hide_admin_toolbar'])) {
    if (!empty($variables['attributes']['class'])) {
      unset($variables['page_top']['toolbar']);
      $variables['attributes']['class'] = array_filter($variables['attributes']['class'], function ($value) {
        return strpos($value, 'toolbar-') !== 0;
      });
    }
  }
}
