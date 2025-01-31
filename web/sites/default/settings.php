<?php

/**
 * @file
 * Drupal site-specific configuration file.
 *
 * The structure of this file:
 * - Environment type constants definitions.
 * - Site-specific settings.
 * - Inclusion of hosting providers settings.
 * - Per-environment overrides.
 * - Inclusion of local settings.
 *
 * Create settings.local.php file to include local settings overrides.
 *
 * phpcs:disable Drupal.Commenting.InlineComment.NoSpaceBefore
 * phpcs:disable Drupal.Commenting.InlineComment.SpacingAfter
 * phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
 * phpcs:disable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
 */

declare(strict_types=1);

////////////////////////////////////////////////////////////////////////////////
///                       ENVIRONMENT TYPE CONSTANTS                         ///
////////////////////////////////////////////////////////////////////////////////

// Use these constants anywhere in code to alter behaviour for a specific
// environment.
// @codeCoverageIgnoreStart
if (!defined('ENVIRONMENT_LOCAL')) {
  define('ENVIRONMENT_LOCAL', 'local');
}
if (!defined('ENVIRONMENT_CI')) {
  define('ENVIRONMENT_CI', 'ci');
}
if (!defined('ENVIRONMENT_PROD')) {
  define('ENVIRONMENT_PROD', 'prod');
}
if (!defined('ENVIRONMENT_STAGE')) {
  define('ENVIRONMENT_STAGE', 'stage');
}
if (!defined('ENVIRONMENT_DEV')) {
  define('ENVIRONMENT_DEV', 'dev');
}
// @codeCoverageIgnoreEnd

$settings['environment'] = empty(getenv('CI')) ? ENVIRONMENT_LOCAL : ENVIRONMENT_CI;

////////////////////////////////////////////////////////////////////////////////
///                       SITE-SPECIFIC SETTINGS                             ///
////////////////////////////////////////////////////////////////////////////////

$app_root = $app_root ?? DRUPAL_ROOT;
$site_path = $site_path ?? 'sites/default';
$contrib_path = $app_root . DIRECTORY_SEPARATOR . (is_dir($app_root . DIRECTORY_SEPARATOR . 'modules/contrib') ? 'modules/contrib' : 'modules');

// Load services definition file.
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

// Location of the site configuration files.
$settings['config_sync_directory'] = getenv('DRUPAL_CONFIG_PATH') ?: '../config/default';

// Private directory.
$settings['file_private_path'] = getenv('DRUPAL_PRIVATE_FILES') ?: 'sites/default/files/private';

// Temporary directory.
$settings['file_temp_path'] = getenv('DRUPAL_TEMPORARY_FILES') ?: '/tmp';

// Base salt on the DB host name.
$settings['hash_salt'] = hash('sha256', getenv('DATABASE_HOST') ?: 'localhost');

// Expiration of cached pages.
$config['system.performance']['cache']['page']['max_age'] = 900;

// Aggregate CSS and JS files.
$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;

// The default list of directories that will be ignored by Drupal's file API.
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

// The default number of entities to update in a batch process.
$settings['entity_update_batch_size'] = 50;

// Trusted Host Patterns.
// Settings for other environments are included below.
// If your site runs on multiple domains, you need to add these domains here.
// escape dots, remove schema, use commas as regex separator.
// See https://www.drupal.org/node/2410395 for more information.
$settings['trusted_host_patterns'] = [
  // Local URL.
  '^.+\.docker\.amazee\.io$',
  // URL when accessed from Behat tests.
  '^nginx$',
];

// Modules excluded from config export.
$settings['config_exclude_modules'] = [];

ini_set('date.timezone', 'Australia/Melbourne');
date_default_timezone_set('Australia/Melbourne');

// Maintenance theme.
$config['maintenance_theme'] = 'your_site_theme';

// Default database configuration.
$databases = [
  'default' =>
    [
      'default' =>
        [
          'database' => getenv('DATABASE_NAME') ?: getenv('DATABASE_DATABASE') ?: getenv('MARIADB_DATABASE') ?: 'drupal',
          'username' => getenv('DATABASE_USERNAME') ?: getenv('MARIADB_USERNAME') ?: 'drupal',
          'password' => getenv('DATABASE_PASSWORD') ?: getenv('MARIADB_PASSWORD') ?: 'drupal',
          'host' => getenv('DATABASE_HOST') ?: getenv('MARIADB_HOST') ?: 'localhost',
          'port' => getenv('DATABASE_PORT') ?: getenv('MARIADB_PORT') ?: '',
          'prefix' => '',
          'driver' => 'mysql',
        ],
    ],
];

////////////////////////////////////////////////////////////////////////////////
///                       ENVIRONMENT TYPE DETECTION                         ///
////////////////////////////////////////////////////////////////////////////////

// Load provider-specific settings.
if (file_exists($app_root . '/' . $site_path . '/includes/providers')) {
  $files = glob($app_root . '/' . $site_path . '/includes/providers/settings.*.php');
  if ($files) {
    foreach ($files as $filename) {
      require $filename;
    }
  }
}

// Allow overriding of an environment type.
if (!empty(getenv('DRUPAL_ENVIRONMENT'))) {
  $settings['environment'] = getenv('DRUPAL_ENVIRONMENT');
}

////////////////////////////////////////////////////////////////////////////////
///                   ENVIRONMENT-SPECIFIC SETTINGS                          ///
////////////////////////////////////////////////////////////////////////////////

if ($settings['environment'] == ENVIRONMENT_CI) {
  // Never harden permissions on sites/default/files.
  $settings['skip_permissions_hardening'] = TRUE;

  // Disable built-in cron trigger.
  $config['automated_cron.settings']['interval'] = 0;

  // Disable mail send out.
  $settings['suspend_mail_send'] = TRUE;
}

if ($settings['environment'] == ENVIRONMENT_LOCAL) {
  // Never harden permissions on sites/default/files during local development.
  $settings['skip_permissions_hardening'] = TRUE;

  // Disable built-in cron trigger.
  $config['automated_cron.settings']['interval'] = 0;

  // Show all error messages on the site.
  $config['system.logging']['error_level'] = 'all';
}

////////////////////////////////////////////////////////////////////////////////
///                       PER-MODULE SETTINGS                                ///
////////////////////////////////////////////////////////////////////////////////

if (file_exists($app_root . '/' . $site_path . '/includes/modules')) {
  $files = glob($app_root . '/' . $site_path . '/includes/modules/settings.*.php');
  if ($files) {
    foreach ($files as $filename) {
      require $filename;
    }
  }
}

////////////////////////////////////////////////////////////////////////////////
///                          LOCAL SETTINGS                                  ///
////////////////////////////////////////////////////////////////////////////////

// Load local development override configuration, if available.
//
// Copy default.settings.local.php and default.services.local.yml to
// settings.local.php and services.local.yml respectively.
// services.local.yml is loaded in in settings.local.php.
//
// Keep this code block at the end of this file to take full effect.
// @codeCoverageIgnoreStart
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  require $app_root . '/' . $site_path . '/settings.local.php';
}
// @codeCoverageIgnoreEnd
