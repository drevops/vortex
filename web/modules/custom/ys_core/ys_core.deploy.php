<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Installs custom theme.
 */
function ys_core_deploy_install_theme() {
  \Drupal::service('theme_installer')->install(['olivero']);
  \Drupal::service('theme_installer')->install(['your_site_theme']);
  \Drupal::service('config.factory')->getEditable('system.theme')->set('default', 'your_site_theme')->save();
}

// phpcs:ignore #;< REDIS
/**
 * Enables Redis module.
 */
function ys_core_deploy_enable_redis() {
  $listing = new ExtensionDiscovery(\Drupal::root());
  $modules = $listing->scan('module');
  if (!empty($modules['redis'])) {
    \Drupal::service('module_installer')->install(['redis']);
  }
}
// phpcs:ignore #;> REDIS
