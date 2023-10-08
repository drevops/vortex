<?php

/**
 * @file
 * Drupal site-specific configuration file.
 *
 * The structure of this file:
 * - Environment constants definitions.
 * - Site-specific settings.
 * - Environment variable initialisation.
 * - Per-environment overrides.
 * - Inclusion of local settings.
 *
 * Create settings.local.php file to include local settings overrides.
 *
 * @phpcs:disable Drupal.Commenting.InlineComment.NoSpaceBefore
 * @phpcs:disable Drupal.Commenting.InlineComment.SpacingAfter
 * @phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
 * @phpcs:disable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
 */

////////////////////////////////////////////////////////////////////////////////
///                       ENVIRONMENT CONSTANTS                              ///
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
if (!defined('ENVIRONMENT_TEST')) {
  define('ENVIRONMENT_TEST', 'test');
}
if (!defined('ENVIRONMENT_DEV')) {
  define('ENVIRONMENT_DEV', 'dev');
}
// @codeCoverageIgnoreEnd

$settings['environment'] = !empty(getenv('CI')) ? ENVIRONMENT_CI : ENVIRONMENT_LOCAL;

////////////////////////////////////////////////////////////////////////////////
///                       SITE-SPECIFIC SETTINGS                             ///
////////////////////////////////////////////////////////////////////////////////
$app_root = $app_root ?? DRUPAL_ROOT;
$site_path = $site_path ?? 'sites/default';
$contrib_path = $app_root . DIRECTORY_SEPARATOR . (is_dir($app_root . DIRECTORY_SEPARATOR . 'modules/contrib') ? 'modules/contrib' : 'modules');

// Load services definition file.
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

// Location of the site configuration files.
$settings['config_sync_directory'] = '../config/default';

// Temporary directory.
if (getenv('TMP')) {
  $settings['file_temp_path'] = getenv('TMP');
}

// Private directory.
$settings['file_private_path'] = 'sites/default/files/private';

// Base salt on the DB host name.
$settings['hash_salt'] = hash('sha256', getenv('MARIADB_HOST') ?: 'localhost');

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
          'database' => getenv('MARIADB_DATABASE') ?: 'drupal',
          'username' => getenv('MARIADB_USERNAME') ?: 'drupal',
          'password' => getenv('MARIADB_PASSWORD') ?: 'drupal',
          'host' => getenv('MARIADB_HOST') ?: 'localhost',
          'port' => getenv('MARIADB_PORT') ?: '',
          'prefix' => '',
          'driver' => 'mysql',
        ],
    ],
];

////////////////////////////////////////////////////////////////////////////////
///                   ENVIRONMENT-SPECIFIC SETTINGS                          ///
////////////////////////////////////////////////////////////////////////////////

// #;< ACQUIA
// Initialise environment type in Acquia environment.
// @see https://docs.acquia.com/acquia-cloud/develop/env-variable
if (!empty(getenv('AH_SITE_ENVIRONMENT'))) {
  // Delay the initial database connection.
  $config['acquia_hosting_settings_autoconnect'] = FALSE;
  // Include Acquia environment settings.
  if (file_exists('/var/www/site-php/your_site/your_site-settings.inc')) {
    // @codeCoverageIgnoreStart
    require '/var/www/site-php/your_site/your_site-settings.inc';
    $settings['config_sync_directory'] = $settings['config_vcs_directory'];
    // @codeCoverageIgnoreEnd
  }
  // Default all environments to 'dev', including ODE environments.
  $settings['environment'] = ENVIRONMENT_DEV;

  // Do not put any Acquia-specific settings in this code block. It is used
  // to explicitly map Acquia environments to $settings['environment']
  // variable only.
  // Instead, use 'PER-ENVIRONMENT SETTINGS' section below.
  switch (getenv('AH_SITE_ENVIRONMENT')) {
    case 'prod':
      $settings['environment'] = ENVIRONMENT_PROD;
      break;

    case 'test':
      $settings['environment'] = ENVIRONMENT_TEST;
      break;
  }
}
// #;> ACQUIA

// #;< LAGOON
// Initialise environment type in Lagoon environment.
if (getenv('LAGOON') && getenv('LAGOON_ENVIRONMENT_TYPE') == 'production' || getenv('LAGOON_ENVIRONMENT_TYPE') == 'development') {
  // Do not put any Lagoon-specific settings in this code block. It is used
  // to explicitly map Lagoon environments to $settings['environment']
  // variable only.
  // Instead, use 'PER-ENVIRONMENT SETTINGS' section below.
  //
  // Environment is marked as 'production' in Lagoon.
  if (getenv('LAGOON_ENVIRONMENT_TYPE') == 'production') {
    $settings['environment'] = ENVIRONMENT_PROD;
  }
  // All other environments running in Lagoon are considered 'development'.
  else {
    // Any other environment is considered 'development' in Lagoon.
    $settings['environment'] = ENVIRONMENT_DEV;

    // But try to identify production environment using a branch name for
    // the cases when 'production' Lagoon environment is not provisioned yet.
    if (!empty(getenv('LAGOON_GIT_BRANCH')) && !empty(getenv('DREVOPS_PRODUCTION_BRANCH')) && getenv('LAGOON_GIT_BRANCH') == getenv('DREVOPS_PRODUCTION_BRANCH')) {
      $settings['environment'] = ENVIRONMENT_PROD;
    }
    // Dedicated test environment based on a branch name.
    elseif (getenv('LAGOON_GIT_BRANCH') == 'master') {
      $settings['environment'] = ENVIRONMENT_TEST;
    }
    // Test environment based on a branch prefix for release and
    // hotfix branches.
    elseif (!empty(getenv('LAGOON_GIT_BRANCH')) && (str_starts_with(getenv('LAGOON_GIT_BRANCH'), 'release/') || str_starts_with(getenv('LAGOON_GIT_BRANCH'), 'hotfix/'))) {
      $settings['environment'] = ENVIRONMENT_TEST;
    }
  }

  // Lagoon version.
  if (!defined('LAGOON_VERSION')) {
    define('LAGOON_VERSION', '1');
  }

  // Lagoon reverse proxy settings.
  $settings['reverse_proxy'] = TRUE;
  // Reverse proxy settings.
  $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';

  // Cache prefix.
  $settings['cache_prefix']['default'] = (getenv('LAGOON_PROJECT') ?: getenv('DREVOPS_PROJECT')) . '_' . (getenv('LAGOON_GIT_SAFE_BRANCH') ?: getenv('DREVOPS_PRODUCTION_BRANCH'));

  // Trusted host patterns for Lagoon internal routes.
  // URL when accessed from PHP processes in Lagoon.
  $settings['trusted_host_patterns'][] = '^nginx\-php$';
  // Lagoon URL.
  $settings['trusted_host_patterns'][] = '^.+\.au\.amazee\.io$';
  // Lagoon routes.
  if (getenv('LAGOON_ROUTES')) {
    $patterns = str_replace(['.', 'https://', 'http://', ','], [
      '\.', '', '', '|',
    ], getenv('LAGOON_ROUTES'));
    $settings['trusted_host_patterns'][] = '^' . $patterns . '$';
  }
}
// #;> LAGOON

// Allow overriding of an environment type.
if (!empty(getenv('DREVOPS_ENVIRONMENT'))) {
  $settings['environment'] = getenv('DREVOPS_ENVIRONMENT');
}

////////////////////////////////////////////////////////////////////////////////
///                         ENVIRONMENT DETECTION                            ///
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

if (file_exists($app_root . '/' . $site_path . '/includes')) {
  $files = glob($app_root . '/' . $site_path . '/includes/settings.*.php');
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
//
// Keep this code block at the end of this file to take full effect.
// @codeCoverageIgnoreStart
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  require $app_root . '/' . $site_path . '/settings.local.php';
}
if (file_exists($app_root . '/' . $site_path . '/services.local.yml')) {
  $settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.local.yml';
}
// @codeCoverageIgnoreEnd
