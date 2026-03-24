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
function ys_demo_deploy_place_counter_block(): string {
  $block_storage = \Drupal::entityTypeManager()->getStorage('block');

  // Check if block already exists.
  $existing_block = $block_storage->load('ys_demo_counter_block');
  if ($existing_block) {
    return 'Counter block already exists';
  }

  // Create block configuration.
  $block_config = [
    'id' => 'ys_demo_counter_block',
    'theme' => \Drupal::config('system.theme')->get('default'),
    'region' => 'content',
    'weight' => 10,
    'plugin' => 'ys_demo_counter_block',
    'settings' => [
      'id' => 'ys_demo_counter_block',
      'label' => 'Counter Block',
      'label_display' => 'visible',
      'provider' => 'ys_demo',
    ],
  ];

  // Create and save the block.
  $block = $block_storage->create($block_config);
  $block->save();

  return 'Counter block placed in the "content" region';
}

/**
 * Create "Articles" menu link in the main navigation.
 *
 * Demonstrates using the drupal_helpers module to manage menu links
 * within deploy hooks.
 *
 * @codeCoverageIgnore
 */
function ys_demo_deploy_create_articles_menu_link(): string {
  $existing = \Drupal\drupal_helpers\Helper::menu()->findItem('main', ['title' => 'Articles']);
  if ($existing) {
    return 'Articles menu link already exists.';
  }

  \Drupal\drupal_helpers\Helper::menu()->createTree('main', [
    'Articles' => '/articles',
  ]);

  return 'Created "Articles" menu link in main navigation.';
}

/**
 * Configure testmode to filter the articles view.
 *
 * Registers the 'ys_demo_articles' view with testmode so that only
 * content matching the [TEST] prefix appears during Behat test runs.
 *
 * @codeCoverageIgnore
 */
function ys_demo_deploy_configure_testmode(): string {
  $testmode = \Drupal\testmode\Testmode::getInstance();

  $views = $testmode->getNodeViews();
  if (!in_array('ys_demo_articles', $views)) {
    $views[] = 'ys_demo_articles';
    $testmode->setNodeViews($views);
  }

  return 'Configured testmode to filter the articles view.';
}
