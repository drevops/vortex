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
define('ENVIRONMENT_LOCAL', 'local');
define('ENVIRONMENT_CI', 'ci');
define('ENVIRONMENT_PROD', 'prod');
define('ENVIRONMENT_TEST', 'test');
define('ENVIRONMENT_DEV', 'dev');
$conf['environment'] = ENVIRONMENT_LOCAL;

////////////////////////////////////////////////////////////////////////////////
///                       SITE-SPECIFIC SETTINGS                             ///
////////////////////////////////////////////////////////////////////////////////

// Example of site-specific settings that should be placed into this section.
// Set the default timezone globally.
ini_set('date.timezone', 'Australia/Melbourne');
date_default_timezone_set('Australia/Melbourne');
$update_free_access = FALSE;
$conf['404_fast_paths_exclude'] = '/\/(?:styles)|(?:system\/files)\//';
$conf['404_fast_paths'] = '/\.(?:txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
$conf['404_fast_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';

////////////////////////////////////////////////////////////////////////////////
///                   END OF SITE-SPECIFIC SETTINGS                          ///
////////////////////////////////////////////////////////////////////////////////

// Include Acquia settings.
// @see https://docs.acquia.com/acquia-cloud/develop/env-variable
if (file_exists('/var/www/site-php')) {
  // Delay the initial database connection.
  $conf['acquia_hosting_settings_autoconnect'] = FALSE;
  // The standard require line goes here.
  require '/var/www/site-php/MYSITE/MYSITE-settings.inc';
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
if (file_exists(DRUPAL_ROOT . '/' . conf_path() . '/settings.beetbox.php')) {
  include DRUPAL_ROOT . '/' . conf_path() . '/settings.beetbox.php';
}

// Load local development override configuration, if available.
//
// Use settings.local.php to override variables on secondary (staging,
// development, etc) installations of this site. Typically used to disable
// caching, JavaScript/CSS compression, re-routing of outgoing emails, and
// other things that should not happen on development and testing sites.
//
// Keep this code block at the end of this file to take full effect.
if (file_exists(DRUPAL_ROOT . '/' . conf_path() . '/settings.local.php')) {
  include DRUPAL_ROOT . '/' . conf_path() . '/settings.local.php';
}
