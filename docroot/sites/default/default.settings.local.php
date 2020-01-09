<?php

/**
 * @file
 * Settings file for local environment.
 */

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

// Skip permissions hardening.
$settings['skip_permissions_hardening'] = TRUE;

// Enable Livereload.
$settings['livereload'] = TRUE;

// Hide admin toolbar. Useful for themeing while logged in as admin.
// $settings['hide_admin_toolbar'] = TRUE;
