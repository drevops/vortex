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
 * - Inclusion of generated settings. The database connection string will be
 *   injected in these generated settings.
 * - Inclusion of local settings.
 *
 * Create settings.local.php file to include local settings overrides.
 *
 * @phpcs:ignoreFile DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
 */

////////////////////////////////////////////////////////////////////////////////
///                       ENVIRONMENT CONSTANTS                              ///
////////////////////////////////////////////////////////////////////////////////

// Use these constants anywhere in code to alter behaviour for a specific
// environment.
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

$settings['environment'] = !empty(getenv('CI')) ? ENVIRONMENT_CI : ENVIRONMENT_LOCAL;

////////////////////////////////////////////////////////////////////////////////
///                       SITE-SPECIFIC SETTINGS                             ///
////////////////////////////////////////////////////////////////////////////////
$app_root = $app_root ?? dirname(__DIR__, 2);
$site_path = $site_path ?? 'sites/default';
$contrib_path = $app_root . DIRECTORY_SEPARATOR . (is_dir('modules/contrib') ? 'modules/contrib' : 'modules');

// Load services definition file.
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

// Location of the site configuration files.
$settings['config_sync_directory'] = '../config/default';

// Temp directory.
if (getenv('TMP')) {
  $settings['file_temp_path'] = getenv('TMP');
}

// Private directory.
$settings['file_private_path'] = 'sites/default/files/private';

// Salt for one-time login links, cancel links, form tokens, etc.
$settings['hash_salt'] = hash('sha256', 'CHANGE_ME');

// Expiration of cached pages on Varnish to 15 min.
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
  // #;< LAGOON
  // URL when accessed from PHP processes in Lagoon.
  '^nginx\-php$',
  // Lagoon URL.
  '^.+\.au\.amazee\.io$',
  // #;> LAGOON
];

// Modules excluded from config export.
$settings['config_exclude_modules'] = [];

// Default Shield credentials.
// Shield can be enabled and disabled in production though UI. For other
// environments, shield is enforced to be enabled.
// 'DRUPAL_SHIELD_USER' and 'DRUPAL_SHIELD_PASS' environment
// variables should be added in the environment.
$config['shield.settings']['credentials']['shield']['user'] = getenv('DRUPAL_SHIELD_USER');
$config['shield.settings']['credentials']['shield']['pass'] = getenv('DRUPAL_SHIELD_PASS');
// Title of the shield pop-up.
$config['shield.settings']['print'] = 'YOURSITE';

ini_set('date.timezone', 'Australia/Melbourne');
date_default_timezone_set('Australia/Melbourne');

// Include additional site-wide settings.
if (file_exists($app_root . '/' . $site_path . '/includes')) {
  foreach (glob($app_root . '/' . $site_path . '/includes/settings.*.php') as $filename) {
    require_once $filename;
  }
}

////////////////////////////////////////////////////////////////////////////////
///                   END OF SITE-SPECIFIC SETTINGS                          ///
////////////////////////////////////////////////////////////////////////////////

// #;< ACQUIA
// Initialise environment type in Acquia environment.
// @see https://docs.acquia.com/acquia-cloud/develop/env-variable
if (!empty(getenv('AH_SITE_ENVIRONMENT'))) {
  // Delay the initial database connection.
  $config['acquia_hosting_settings_autoconnect'] = FALSE;
  // Include Acquia environment settings.
  if (file_exists('/var/www/site-php/your_site/your_site-settings.inc')) {
    require '/var/www/site-php/your_site/your_site-settings.inc';
  }
  // Do not put any Acquia-specific settings in this code block. It is used
  // to explicitly map Acquia environments to $conf['environment']
  // variable only.
  // Instead, use 'PER-ENVIRONMENT SETTINGS' section below.
  switch (getenv('AH_SITE_ENVIRONMENT')) {
    case 'prod':
      $settings['environment'] = ENVIRONMENT_PROD;
      break;

    case 'test':
      $settings['environment'] = ENVIRONMENT_TEST;
      break;

    case 'dev':
      $settings['environment'] = ENVIRONMENT_DEV;
      break;
  }

  // Treat ODE environments as 'dev'.
  if (strpos(getenv('AH_SITE_ENVIRONMENT'), 'ode') === 0) {
    $settings['environment'] = ENVIRONMENT_DEV;
  }
}
// #;> ACQUIA

// #;< LAGOON
// Initialise environment type in Lagoon environment.
if (getenv('LAGOON')) {
  if (getenv('LAGOON_ENVIRONMENT_TYPE') == 'production') {
    $settings['environment'] = ENVIRONMENT_PROD;
  }
  // Use a dedicated branch to identify production environment.
  // This is useful when 'production' Lagoon environment is not provisioned yet.
  elseif (!empty(getenv('LAGOON_GIT_BRANCH')) && !empty(getenv('DREVOPS_PRODUCTION_BRANCH')) && getenv('LAGOON_GIT_BRANCH') == getenv('DREVOPS_PRODUCTION_BRANCH')) {
    $settings['environment'] = ENVIRONMENT_PROD;
  }
  elseif (getenv('LAGOON_ENVIRONMENT_TYPE') == 'development') {
    $settings['environment'] = ENVIRONMENT_DEV;
  }

  // Lagoon version.
  if (!defined('LAGOON_VERSION')) {
    define('LAGOON_VERSION', '1');
  }

  // Lagoon reverse proxy settings.
  $settings['reverse_proxy'] = TRUE;

  // Trusted host patterns for Lagoon internal routes.
  // Do not add vanity domains here. Use the $settings['trusted_host_patterns']
  // array in a previous section.
  if (getenv('LAGOON_ROUTES')) {
    $patterns = str_replace(['.', 'https://', 'http://', ','], [
      '\.', '', '', '|',
    ], getenv('LAGOON_ROUTES'));
    $settings['trusted_host_patterns'][] = '^' . $patterns . '$';
  }

  // Hash salt.
  // DREVOPS_MARIADB_HOST on Lagoon is a randomly generated service name.
  $settings['hash_salt'] = hash('sha256', getenv('DREVOPS_MARIADB_HOST'));
}
// #;> LAGOON

////////////////////////////////////////////////////////////////////////////////
///                       PER-ENVIRONMENT SETTINGS                           ///
////////////////////////////////////////////////////////////////////////////////

if ($settings['environment'] == ENVIRONMENT_PROD) {
  $config['environment_indicator.indicator']['name'] = $settings['environment'];
  $config['environment_indicator.indicator']['bg_color'] = '#ef5350';
  $config['environment_indicator.indicator']['fg_color'] = '#000000';
  $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
  $config['environment_indicator.settings']['favicon'] = TRUE;
}
else {
  $config['stage_file_proxy.settings']['origin'] = sprintf('https://%s:%s@your-site-url.example/', getenv('DRUPAL_SHIELD_USER'), getenv('DRUPAL_SHIELD_PASS'));
  $config['stage_file_proxy.settings']['hotlink'] = FALSE;

  $config['environment_indicator.indicator']['name'] = $settings['environment'];
  $config['environment_indicator.indicator']['bg_color'] = '#006600';
  $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
  $config['environment_indicator.settings']['toolbar_integration'] = [TRUE];
  $config['environment_indicator.settings']['favicon'] = TRUE;

  // Enforce shield.
  $config['shield.settings']['shield_enable'] = TRUE;
}

if ($settings['environment'] == ENVIRONMENT_TEST) {
  $config['config_split.config_split.test']['status'] = TRUE;

  $config['environment_indicator.indicator']['bg_color'] = '#fff176';
  $config['environment_indicator.indicator']['fg_color'] = '#000000';
}

if ($settings['environment'] == ENVIRONMENT_DEV) {
  $config['config_split.config_split.dev']['status'] = TRUE;

  $config['environment_indicator.indicator']['bg_color'] = '#4caf50';
  $config['environment_indicator.indicator']['fg_color'] = '#000000';
}

if ($settings['environment'] == ENVIRONMENT_CI) {
  $config['config_split.config_split.ci']['status'] = TRUE;

  // Never harden permissions on sites/default/files.
  $settings['skip_permissions_hardening'] = TRUE;

  // Bypass shield.
  $config['shield.settings']['shield_enable'] = FALSE;

  // Disable mail send out.
  $settings['suspend_mail_send'] = TRUE;
}

if ($settings['environment'] == ENVIRONMENT_LOCAL) {
  // Show all error messages on the site.
  $config['system.logging']['error_level'] = 'all';

  // Enable local split.
  $config['config_split.config_split.local']['status'] = TRUE;

  // Never harden permissions on sites/default/files during local development.
  $settings['skip_permissions_hardening'] = TRUE;

  // Bypass shield.
  $config['shield.settings']['shield_enable'] = FALSE;
}

////////////////////////////////////////////////////////////////////////////////
///                    END OF PER-ENVIRONMENT SETTINGS                       ///
////////////////////////////////////////////////////////////////////////////////

// Include generated settings file, if available.
if (file_exists($app_root . '/' . $site_path . '/settings.generated.php')) {
  include $app_root . '/' . $site_path . '/settings.generated.php';
}

// Load local development override configuration, if available.
//
// Use settings.local.php to override variables on secondary (staging,
// development, etc.) installations of this site. Typically, used to disable
// caching, JavaScript/CSS compression, re-routing of outgoing emails, and
// other things that should not happen on development and testing sites.
//
// Copy default.settings.local.php and default.services.local.yml to
// settings.local.php and services.local.yml respectively.
//
// Keep this code block at the end of this file to take full effect.
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
if (file_exists($app_root . '/' . $site_path . '/services.local.yml')) {
  $settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.local.yml';
}
