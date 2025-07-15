<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

/**
 * Place counter block in the "content" region.
 *
 * @codeCoverageIgnore
 */
function ys_base_deploy_place_counter_block(): string {
  $block_storage = \Drupal::entityTypeManager()->getStorage('block');

  // Check if block already exists.
  $existing_block = $block_storage->load('ys_base_counter_block');
  if ($existing_block) {
    return 'Counter block already exists';
  }

  // Create block configuration.
  $block_config = [
    'id' => 'ys_base_counter_block',
    'theme' => \Drupal::config('system.theme')->get('default'),
    'region' => 'content',
    'weight' => 10,
    'plugin' => 'ys_base_counter_block',
    'settings' => [
      'id' => 'ys_base_counter_block',
      'label' => 'Counter Block',
      'label_display' => 'visible',
      'provider' => 'ys_base',
    ],
  ];

  // Create and save the block.
  $block = $block_storage->create($block_config);
  $block->save();

  return 'Counter block placed in the "content" region';
}

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
