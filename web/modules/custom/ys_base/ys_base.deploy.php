<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

// phpcs:ignore #;< DRUPAL_THEME

/**
 * Installs custom theme.
 *
 * @codeCoverageIgnore
 */
function ys_base_deploy_install_theme(): void {
  \Drupal::service('theme_installer')->install(['olivero']);
  \Drupal::service('theme_installer')->install(['your_site_theme']);
  \Drupal::service('config.factory')->getEditable('system.theme')->set('default', 'your_site_theme')->save();
}
// phpcs:ignore #;> DRUPAL_THEME
