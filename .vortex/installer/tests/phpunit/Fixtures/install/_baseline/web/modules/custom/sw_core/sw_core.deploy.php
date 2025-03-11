<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

/**
 * Installs custom theme.
 */
function sw_core_deploy_install_theme(): void {
  \Drupal::service('theme_installer')->install(['olivero']);
  \Drupal::service('theme_installer')->install(['star_wars']);
  \Drupal::service('config.factory')->getEditable('system.theme')->set('default', 'star_wars')->save();
}
