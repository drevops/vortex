<?php

/**
 * @file
 * Storybook settings.
 */

declare(strict_types=1);

$settings['config_exclude_modules'][] = 'storybook';

if (in_array($settings['environment'], [ENVIRONMENT_LOCAL, ENVIRONMENT_CI, ENVIRONMENT_DEV], TRUE)) {
  // Development mode opens the story render route and bypasses render caching
  // and asset aggregation on that route alone.
  $settings['container_yamls'][] = $app_root . '/' . $site_path . '/includes/modules/services.storybook.yml';
}
