<?php

/**
 * @file
 * Settings file for local environment.
 */

declare(strict_types=1);

if (file_exists($app_root . '/' . $site_path . '/services.local.yml')) {
  $settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.local.yml';
}

// Show all error messages on the site.
$config['system.logging']['error_level'] = 'all';

// Disable caching.
$config['system.performance']['cache']['page']['max_age'] = 0;
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
$settings['cache']['bins']['page'] = 'cache.backend.null';
$settings['extension_discovery_scan_tests'] = FALSE;

// Disable CSS files aggregation.
$config['system.performance']['css']['preprocess'] = FALSE;

// Disable JavaScript files aggregation.
$config['system.performance']['js']['preprocess'] = FALSE;

// Hide admin toolbar. Useful for themeing while logged in as admin.
// $settings['hide_admin_toolbar'] = TRUE;
