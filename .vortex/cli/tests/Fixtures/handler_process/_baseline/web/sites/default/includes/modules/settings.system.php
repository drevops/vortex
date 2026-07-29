<?php

/**
 * @file
 * System module settings.
 */

declare(strict_types=1);

use DrevOps\EnvironmentDetector\Environment;

// Expiration of cached pages.
$config['system.performance']['cache']['page']['max_age'] = (int) (getenv('DRUPAL_CACHE_PAGE_MAX_AGE') ?: 900);

if ($settings['environment'] === Environment::PRODUCTION) {
  // Always aggregate CSS and JS files in production.
  $config['system.performance']['css']['preprocess'] = TRUE;
  $config['system.performance']['js']['preprocess'] = TRUE;
}

if ($settings['environment'] === Environment::LOCAL || $settings['environment'] === Environment::CI) {
  // Never harden permissions on sites/default/files.
  $settings['skip_permissions_hardening'] = TRUE;
  // Show all error messages on the site.
  $config['system.logging']['error_level'] = 'all';
}
