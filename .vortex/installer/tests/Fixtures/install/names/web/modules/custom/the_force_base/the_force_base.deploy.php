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
function the_force_base_deploy_place_counter_block(): string {
  $block_storage = \Drupal::entityTypeManager()->getStorage('block');

  // Check if block already exists.
  $existing_block = $block_storage->load('the_force_base_counter_block');
  if ($existing_block) {
    return 'Counter block already exists';
  }

  // Create block configuration.
  $block_config = [
    'id' => 'the_force_base_counter_block',
    'theme' => \Drupal::config('system.theme')->get('default'),
    'region' => 'content',
    'weight' => 10,
    'plugin' => 'the_force_base_counter_block',
    'settings' => [
      'id' => 'the_force_base_counter_block',
      'label' => 'Counter Block',
      'label_display' => 'visible',
      'provider' => 'the_force_base',
    ],
  ];

  // Create and save the block.
  $block = $block_storage->create($block_config);
  $block->save();

  return 'Counter block placed in the "content" region';
}

/**
 * Installs custom theme.
 *
 * @codeCoverageIgnore
 */
function the_force_base_deploy_install_theme(): void {
  \Drupal::service('theme_installer')->install(['olivero']);
  \Drupal::service('theme_installer')->install(['lightsaber']);
  \Drupal::service('config.factory')->getEditable('system.theme')->set('default', 'lightsaber')->save();
}
