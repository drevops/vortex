<?php

/**
 * @file
 * Drupal settings.
 *
 * Create settings.local.php file to include local settings overrides.
 */

// Environment constants.
// Use these constants anywhere in code to alter behaviour for specific
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
$conf['environment'] = ENVIRONMENT_LOCAL;

////////////////////////////////////////////////////////////////////////////////
///                       SITE-SPECIFIC SETTINGS                             ///
////////////////////////////////////////////////////////////////////////////////

/**
 * Location of the site configuration files.
 */
$config_directories = [
  CONFIG_SYNC_DIRECTORY => '../sync',
];

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 */
$settings['hash_salt'] = 'CHANGE_ME';

$settings['update_free_access'] = FALSE;

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

/**
 * The default list of directories that will be ignored by Drupal's file API.
 */
$settings['file_scan_ignore_directories'] = [
  'node_modules',
];

/**
 * The default number of entities to update in a batch process.
 */
$settings['entity_update_batch_size'] = 50;

////////////////////////////////////////////////////////////////////////////////
///                   END OF SITE-SPECIFIC SETTINGS                          ///
////////////////////////////////////////////////////////////////////////////////

// Include Acquia settings.
// @see https://docs.acquia.com/acquia-cloud/develop/env-variable
if (file_exists('/var/www/site-php')) {
  // Delay the initial database connection.
  $conf['acquia_hosting_settings_autoconnect'] = FALSE;
  // The standard require line goes here.
  require '/var/www/site-php/mysite/mysite-settings.inc';
  switch ($_ENV['AH_SITE_ENVIRONMENT']) {
    case 'dev':
      $conf['environment'] = ENVIRONMENT_DEV;
      break;

    case 'test':
      $conf['environment'] = ENVIRONMENT_TEST;
      break;

    case 'prod':
      $conf['environment'] = ENVIRONMENT_PROD;
      break;
  }
}

// Include generated beetbox settings file, if available.
if (file_exists($app_root . '/' . $site_path . '/settings.beetbox.php')) {
  include $app_root . '/' . $site_path . '/settings.beetbox.php';
}

/**
 * Load local development override configuration, if available.
 *
 * Use settings.local.php to override variables on secondary (staging,
 * development, etc) installations of this site. Typically used to disable
 * caching, JavaScript/CSS compression, re-routing of outgoing emails, and
 * other things that should not happen on development and testing sites.
 *
 * Keep this code block at the end of this file to take full effect.
 */
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
