<?php

/**
 * @file
 * Drupal site-specific configuration file.
 *
 * Copy `example.settings.local.php` and `example.services.local.yml` to
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
          'database' => (getenv('DATABASE_NAME') ?: getenv('DATABASE_DATABASE') ?: getenv('MARIADB_DATABASE')) ?: 'drupal',
          'username' => (getenv('DATABASE_USERNAME') ?: getenv('MARIADB_USERNAME')) ?: 'drupal',
          'password' => (getenv('DATABASE_PASSWORD') ?: getenv('MARIADB_PASSWORD')) ?: 'drupal',
          'host' => (getenv('DATABASE_HOST') ?: getenv('MARIADB_HOST')) ?: 'localhost',
          'port' => (getenv('DATABASE_PORT') ?: getenv('MARIADB_PORT')) ?: '3306',
          'charset' => (getenv('DATABASE_CHARSET') ?: getenv('MARIADB_CHARSET') ?: getenv('MYSQL_CHARSET')) ?: 'utf8mb4',
          'collation' => (getenv('DATABASE_COLLATION') ?: getenv('MARIADB_COLLATION') ?: getenv('MYSQL_COLLATION')) ?: 'utf8mb4_general_ci',
          'prefix' => '',
          'driver' => 'mysql',
        ],
    ],
];

////////////////////////////////////////////////////////////////////////////////
///                               GENERAL                                    ///
////////////////////////////////////////////////////////////////////////////////
// @see https://www.vortextemplate.com/docs/drupal/settings#general

$app_root ??= DRUPAL_ROOT;
$site_path ??= 'sites/default';
// @phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
$contrib_path = $app_root . '/' . (is_dir($app_root . '/modules/contrib') ? 'modules/contrib' : 'modules');

// Public files directory relative to the Drupal root.
$settings['file_public_path'] = getenv('DRUPAL_PUBLIC_FILES') ?: 'sites/default/files';

// Private files directory relative to the Drupal root.
$settings['file_private_path'] = getenv('DRUPAL_PRIVATE_FILES') ?: 'sites/default/files/private';

// Temporary file directory.
$settings['file_temp_path'] = (getenv('DRUPAL_TEMPORARY_FILES') ?: getenv('TMP')) ?: '/tmp';

// Location of the site configuration files relative to the Drupal root.
$settings['config_sync_directory'] = getenv('DRUPAL_CONFIG_PATH') ?: '../config/default';

// Load services definition file.
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

// Use DRUPAL_HASH_SALT or the database host name for salt.
$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT') ?: hash('sha256', $databases['default']['default']['host']);

// Timezone settings.
ini_set('date.timezone', (getenv('DRUPAL_TIMEZONE') ?: getenv('TZ')) ?: 'UTC');
date_default_timezone_set((getenv('DRUPAL_TIMEZONE') ?: getenv('TZ')) ?: 'UTC');

// Maintenance theme.
$settings['maintenance_theme'] = (getenv('DRUPAL_MAINTENANCE_THEME') ?: getenv('DRUPAL_THEME')) ?: 'claro';

// Modules excluded from config export.
// Populate this array in the `includes/modules/settings.<module>.php` file.
$settings['config_exclude_modules'] = [];

// The default list of directories that will be ignored by Drupal's file API.
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

// Do not automatically create an Apache HTTP .htaccess file in writable
// directories. Overridden in hosting provider settings files as needed.
$settings['auto_create_htaccess'] = FALSE;

// The default number of entities to update in a batch process.
$settings['entity_update_batch_size'] = 50;

////////////////////////////////////////////////////////////////////////////////
///                       ENVIRONMENT TYPE DETECTION                         ///
////////////////////////////////////////////////////////////////////////////////
// @see https://www.vortextemplate.com/docs/drupal/settings#environment-type-detection

// Detect the environment type from the hosting platform and apply the
// platform-related settings.
require $app_root . '/../vendor/drevops/environment-detector/environment.drupal.php';

////////////////////////////////////////////////////////////////////////////////
///                       PER-MODULE OVERRIDES                               ///
////////////////////////////////////////////////////////////////////////////////
// @see https://www.vortextemplate.com/docs/drupal/settings#per-module-overrides

if (file_exists($app_root . '/' . $site_path . '/includes/modules')) {
  $files = glob($app_root . '/' . $site_path . '/includes/modules/settings.*.php');
  if ($files) {
    foreach ($files as $file) {
      require $file;
    }
  }
}

////////////////////////////////////////////////////////////////////////////////
///                          LOCAL OVERRIDE                                  ///
////////////////////////////////////////////////////////////////////////////////
// @see https://www.vortextemplate.com/docs/drupal/settings#local-overrides

// Load local override configuration, if available.
//
// Copy `example.settings.local.php` and `example.services.local.yml` to
// `settings.local.php` and `services.local.yml` respectively to enable local
// overrides.
//
// `services.local.yml` is loaded from within `settings.local.php`.
//
// Keep this code block at the end of this file to take full effect.
// @codeCoverageIgnoreStart
if (file_exists($app_root . '/' . $site_path . '/settings.local.php') && getenv('DRUPAL_SETTINGS_LOCAL_SKIP') !== '1') {
  require $app_root . '/' . $site_path . '/settings.local.php';
}
// @codeCoverageIgnoreEnd
