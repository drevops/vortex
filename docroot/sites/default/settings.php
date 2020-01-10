<?php

/**
 * @file
 * Drupal settings.
 *
 * The structure of this file:
 * - Environment constants definitions.
 * - Site-specific settings.
 * - Environment variable initialisation.
 * - Per-environment overrides.
 * - Inclusion of generated settings.
 * - Inclusion of local settings.
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

$conf['environment'] = getenv('CI') ? ENVIRONMENT_CI : ENVIRONMENT_LOCAL;

////////////////////////////////////////////////////////////////////////////////
///                       SITE-SPECIFIC SETTINGS                             ///
////////////////////////////////////////////////////////////////////////////////

ini_set('date.timezone', 'Australia/Melbourne');
date_default_timezone_set('Australia/Melbourne');

// Salt for one-time login links, cancel links, form tokens, etc.
$drupal_hash_salt = 'CHANGE_ME';

$update_free_access = FALSE;

ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 200000);
ini_set('session.cookie_lifetime', 2000000);

$conf['404_fast_paths_exclude'] = '/\/(?:styles)|(?:system\/files)\//';
$conf['404_fast_paths'] = '/\.(?:txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
$conf['404_fast_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';

// Default Shield credentials.
// Note that they are overridden for local and CI environments below.
$conf['shield_user'] = 'CHANGEME';
$conf['shield_pass'] = 'CHANGEME';
// Title of the shield pop-up.
$conf['shield_print'] = 'YOURSITE';

////////////////////////////////////////////////////////////////////////////////
///                   END OF SITE-SPECIFIC SETTINGS                          ///
////////////////////////////////////////////////////////////////////////////////

// #;< ACQUIA
// Include Acquia settings.
// @see https://docs.acquia.com/acquia-cloud/develop/env-variable
if (file_exists('/var/www/site-php')) {
  // Delay the initial database connection.
  $conf['acquia_hosting_settings_autoconnect'] = FALSE;
  require '/var/www/site-php/your_site/your_site-settings.inc';
  // Do not put any Acquia-specific settings in this code block. It is used
  // for explicit mapping of Acquia environments to $conf['environment']
  // variable only. Instead, use 'PER-ENVIRONMENT SETTINGS' section below.
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
// #;> ACQUIA

////////////////////////////////////////////////////////////////////////////////
///                       PER-ENVIRONMENT SETTINGS                           ///
////////////////////////////////////////////////////////////////////////////////

$conf['environment_indicator_overwrite'] = TRUE;
$conf['environment_indicator_overwritten_name'] = $conf['environment'];
$conf['environment_indicator_overwritten_color'] = $conf['environment'] == ENVIRONMENT_PROD ? '#ef5350' : '#006600';
$conf['environment_indicator_overwritten_text_color'] = $conf['environment'] == ENVIRONMENT_PROD ? '#000000' : '#ffffff';
$conf['environment_indicator_overwritten_position'] = 'top';
$conf['environment_indicator_overwritten_fixed'] = FALSE;
$conf['environment_indicator_git_support'] = FALSE;

if ($conf['environment'] == ENVIRONMENT_PROD) {
  // Bypass Shield.
  $conf['shield_user'] = '';
  $conf['shield_pass'] = '';
}

if ($conf['environment'] !== ENVIRONMENT_PROD) {
  $conf['stage_file_proxy_origin'] = 'http://your-site-url/';
  $conf['stage_file_proxy_hotlink'] = FALSE;
}

if ($conf['environment'] == ENVIRONMENT_TEST) {
  $conf['environment_indicator_overwritten_color'] = '#fff176';
  $conf['environment_indicator_overwritten_text_color'] = '#000000';
}

if ($conf['environment'] == ENVIRONMENT_DEV) {
  $conf['environment_indicator_overwritten_color'] = '#4caf50';
  $conf['environment_indicator_overwritten_text_color'] = '#000000';
}

if ($conf['environment'] == ENVIRONMENT_CI) {
  // Allow to bypass Shield.
  $conf['shield_user'] = '';
  $conf['shield_pass'] = '';

  // Never harden permissions on sites/default/files.
  $conf['skip_permissions_hardening'] = TRUE;

  // Disable mail send out.
  $conf['suspend_mail_send'] = TRUE;
}

if ($conf['environment'] == ENVIRONMENT_LOCAL) {
  // Show all error messages on the site.
  $conf['error_level'] = 2;

  // Never harden permissions on sites/default/files during local development.
  $conf['skip_permissions_hardening'] = TRUE;

  // Bypass Shield.
  $conf['shield_user'] = '';
  $conf['shield_pass'] = '';
}

////////////////////////////////////////////////////////////////////////////////
///                    END OF PER-ENVIRONMENT SETTINGS                       ///
////////////////////////////////////////////////////////////////////////////////

// Include generated settings file, if available.
if (file_exists(DRUPAL_ROOT . '/' . conf_path() . '/settings.generated.php')) {
  include DRUPAL_ROOT . '/' . conf_path() . '/settings.generated.php';
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
