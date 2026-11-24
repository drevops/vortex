<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

/**
 * Installs default and custom theme.
 *
 * @codeCoverageIgnore
 */
function ys_base_deploy_install_active_theme(): void {
  \Drupal::service('theme_installer')->install(['olivero']);
  \Drupal::service('config.factory')->getEditable('system.theme')->set('default', 'olivero')->save();
  // phpcs:ignore #;< DRUPAL_THEME
  \Drupal::service('theme_installer')->install(['your_site_theme']);
  \Drupal::service('config.factory')->getEditable('system.theme')->set('default', 'your_site_theme')->save();
  // phpcs:ignore #;> DRUPAL_THEME
}
