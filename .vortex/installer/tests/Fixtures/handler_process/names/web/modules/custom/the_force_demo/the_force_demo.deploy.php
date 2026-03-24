<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\drupal_helpers\Helper;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\testmode\Testmode;

/**
 * Place counter block in the "content" region.
 *
 * @codeCoverageIgnore
 */
function the_force_demo_deploy_place_counter_block(): string {
  $block_storage = \Drupal::entityTypeManager()->getStorage('block');

  // Check if block already exists.
  $existing_block = $block_storage->load('the_force_demo_counter_block');
  if ($existing_block) {
    return 'Counter block already exists';
  }

  // Create block configuration.
  $block_config = [
    'id' => 'the_force_demo_counter_block',
    'theme' => \Drupal::config('system.theme')->get('default'),
    'region' => 'content',
    'weight' => 10,
    'plugin' => 'the_force_demo_counter_block',
    'settings' => [
      'id' => 'the_force_demo_counter_block',
      'label' => 'Counter Block',
      'label_display' => 'visible',
      'provider' => 'the_force_demo',
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
function the_force_demo_deploy_create_articles_menu_link(): string {
  $existing = Helper::menu()->findItem('main', ['title' => 'Articles']);
  if ($existing instanceof MenuLinkContentInterface) {
    return 'Articles menu link already exists.';
  }

  Helper::menu()->createTree('main', [
    'Articles' => '/articles',
  ]);

  return 'Created "Articles" menu link in main navigation.';
}

/**
 * Configure testmode to filter the articles view.
 *
 * Registers the 'the_force_demo_articles' view with testmode so that only
 * content matching the [TEST] prefix appears during Behat test runs.
 *
 * @codeCoverageIgnore
 */
function the_force_demo_deploy_configure_testmode(): string {
  $testmode = Testmode::getInstance();

  $views = $testmode->getNodeViews();
  if (!in_array('the_force_demo_articles', $views)) {
    $views[] = 'the_force_demo_articles';
    $testmode->setNodeViews($views);
  }

  return 'Configured testmode to filter the articles view.';
}
