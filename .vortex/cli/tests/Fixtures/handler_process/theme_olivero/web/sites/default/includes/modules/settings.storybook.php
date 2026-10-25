<?php

/**
 * @file
 * Storybook settings.
 */

declare(strict_types=1);

use DrevOps\EnvironmentDetector\Environment;

$settings['config_exclude_modules'][] = 'storybook';

if (in_array($settings['environment'], [Environment::LOCAL, Environment::CI, Environment::DEVELOPMENT], TRUE)) {
  // Development mode opens the story render route and bypasses render caching
  // and asset aggregation on that route alone.
  $settings['container_yamls'][] = $app_root . '/' . $site_path . '/includes/modules/services.storybook.yml';
}
