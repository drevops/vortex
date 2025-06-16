<?php

/**
 * @file
 * Drupal site-specific configuration file.
 *
 * Copy `default.settings.local.php` and `default.services.local.yml` to
 * `settings.local.php` and `services.local.yml` respectively to
 * enable local overrides.
 *
 * It’s recommended to leave this file unchanged and manage configuration
 * through environment variables and module-specific settings files instead.
 * This allows for better portability and easier management of settings across
 * environments.
 * @see https://www.vortextemplate.com/docs/drupal/settings
 *
 * phpcs:disable Drupal.Commenting.InlineComment.NoSpaceBefore
 * phpcs:disable Drupal.Commenting.InlineComment.SpacingAfter
 * phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
 * phpcs:disable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
 */

declare(strict_types=1);

////////////////////////////////////////////////////////////////////////////////
///                               DATABASE                                   ///
////////////////////////////////////////////////////////////////////////////////
// @see https://www.vortextemplate.com/docs/drupal/settings#database

$databases = [
  'default' =>
    [
      'default' =>
        [
          'database' => getenv('DATABASE_NAME') ?: getenv('DATABASE_DATABASE') ?: getenv('MARIADB_DATABASE') ?: 'drupal',
          'username' => getenv('DATABASE_USERNAME') ?: getenv('MARIADB_USERNAME') ?: 'drupal',
          'password' => getenv('DATABASE_PASSWORD') ?: getenv('MARIADB_PASSWORD') ?: 'drupal',
          'host' => getenv('DATABASE_HOST') ?: getenv('MARIADB_HOST') ?: 'localhost',
          'port' => getenv('DATABASE_PORT') ?: getenv('MARIADB_PORT') ?: '3306',
          'charset' => getenv('DATABASE_CHARSET') ?: getenv('MARIADB_CHARSET') ?: getenv('MYSQL_CHARSET') ?: 'utf8mb4',
          'collation' => getenv('DATABASE_COLLATION') ?: getenv('MARIADB_COLLATION') ?: getenv('MYSQL_COLLATION') ?: 'utf8mb4_general_ci',
          'prefix' => '',
          'driver' => 'mysql',
        ],
    ],
];

////////////////////////////////////////////////////////////////////////////////
///                               GENERAL                                    ///
////////////////////////////////////////////////////////////////////////////////
// @see https://www.vortextemplate.com/docs/drupal/settings#general

$app_root = $app_root ?? DRUPAL_ROOT;
$site_path = $site_path ?? 'sites/default';
$contrib_path = $app_root . '/' . (is_dir($app_root . '/modules/contrib') ? 'modules/contrib' : 'modules');

// Public files directory relative to the Drupal root.
$settings['file_public_path'] = getenv('DRUPAL_PUBLIC_FILES') ?: 'sites/default/files';

// Private files directory relative to the Drupal root.
$settings['file_private_path'] = getenv('DRUPAL_PRIVATE_FILES') ?: 'sites/default/files/private';

// Temporary file directory.
$settings['file_temp_path'] = getenv('DRUPAL_TEMPORARY_FILES') ?: getenv('TMP') ?: '/tmp';

// Location of the site configuration files relative to the Drupal root. If not
// set, the default location is inside a randomly-named directory in the public
// files path.
if (!empty(getenv('DRUPAL_CONFIG_PATH'))) {
  $settings['config_sync_directory'] = getenv('DRUPAL_CONFIG_PATH');
}

// Load services definition file.
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

// Use DRUPAL_HASH_SALT or the database host name for salt.
$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT') ?: hash('sha256', $databases['default']['default']['host']);

// Timezone settings.
ini_set('date.timezone', getenv('DRUPAL_TIMEZONE') ?: getenv('TZ') ?: 'UTC');
date_default_timezone_set(getenv('DRUPAL_TIMEZONE') ?: getenv('TZ') ?: 'UTC');

// Maintenance theme.
$settings['maintenance_theme'] = getenv('DRUPAL_MAINTENANCE_THEME') ?: getenv('DRUPAL_THEME') ?: 'claro';

// Trusted Host Patterns.
// See https://www.drupal.org/node/2410395 for more information on how to
// populate this array.
// Settings for specific environments (including a local container-based
// environment) are populated within provider-specific
// `includes/providers/settings.<provider>.php` files.
// @see https://www.vortextemplate.com/docs/drupal/settings#per-module-overrides
$settings['trusted_host_patterns'] = [
  '^localhost$',
];

// Modules excluded from config export.
// Populate this array in the `includes/modules/settings.<module>.php` file.
$settings['config_exclude_modules'] = [];

// The default list of directories that will be ignored by Drupal's file API.
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

// The default number of entities to update in a batch process.
$settings['entity_update_batch_size'] = 50;

////////////////////////////////////////////////////////////////////////////////
///                       ENVIRONMENT TYPE DETECTION                         ///
////////////////////////////////////////////////////////////////////////////////
// @see https://www.vortextemplate.com/docs/drupal/settings#environment-type-detection

// Use these constants anywhere in code to alter behavior for a specific
// environment.
// @codeCoverageIgnoreStart
if (!defined('ENVIRONMENT_LOCAL')) {
  define('ENVIRONMENT_LOCAL', 'local');
}
if (!defined('ENVIRONMENT_CI')) {
  define('ENVIRONMENT_CI', 'ci');
}
if (!defined('ENVIRONMENT_DEV')) {
  define('ENVIRONMENT_DEV', 'dev');
}
if (!defined('ENVIRONMENT_STAGE')) {
  define('ENVIRONMENT_STAGE', 'stage');
}
if (!defined('ENVIRONMENT_PROD')) {
  define('ENVIRONMENT_PROD', 'prod');
}
// @codeCoverageIgnoreEnd

// Default environment type is 'local'.
$settings['environment'] = ENVIRONMENT_LOCAL;

// Load provider-specific environment detection settings.
if (file_exists($app_root . '/' . $site_path . '/includes/providers')) {
  $files = glob($app_root . '/' . $site_path . '/includes/providers/settings.*.php');
  if ($files) {
    foreach ($files as $filename) {
      require $filename;
    }
  }
}

// Allow to override an environment type using the DRUPAL_ENVIRONMENT variable.
if (!empty(getenv('DRUPAL_ENVIRONMENT'))) {
  $settings['environment'] = getenv('DRUPAL_ENVIRONMENT');
}

////////////////////////////////////////////////////////////////////////////////
///                       PER-MODULE OVERRIDES                               ///
////////////////////////////////////////////////////////////////////////////////
// @see https://www.vortextemplate.com/docs/drupal/settings#per-module-overrides

if (file_exists($app_root . '/' . $site_path . '/includes/modules')) {
  $files = glob($app_root . '/' . $site_path . '/includes/modules/settings.*.php');
  if ($files) {
    foreach ($files as $filename) {
      require $filename;
    }
  }
}

////////////////////////////////////////////////////////////////////////////////
///                          LOCAL OVERRIDE                                  ///
////////////////////////////////////////////////////////////////////////////////
// @see https://www.vortextemplate.com/docs/drupal/settings#local-overrides

// Load local override configuration, if available.
//
// Copy `default.settings.local.php` and `default.services.local.yml` to
// `settings.local.php` and `services.local.yml` respectively to enable local
// overrides.
//
// `services.local.yml` is loaded from within `settings.local.php`.
//
// Keep this code block at the end of this file to take full effect.
// @codeCoverageIgnoreStart
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  require $app_root . '/' . $site_path . '/settings.local.php';
}
// @codeCoverageIgnoreEnd
