<?php

/**
 * @file
 * Shield settings.
 */

// Default Shield credentials.
// Shield can be enabled and disabled in production though UI.
// For other environments, Shield is enforced to be enabled.
// 'DRUPAL_SHIELD_USER' and 'DRUPAL_SHIELD_PASS' environment variables should
// be added in the environment.
// Check fo existence of variables to prevent locking out.
if (!empty(getenv('DRUPAL_SHIELD_USER')) && !empty(getenv('DRUPAL_SHIELD_PASS'))) {
  $config['shield.settings']['credentials']['shield']['user'] = getenv('DRUPAL_SHIELD_USER');
  $config['shield.settings']['credentials']['shield']['pass'] = getenv('DRUPAL_SHIELD_PASS');
}
// Title of the shield pop-up.
$config['shield.settings']['print'] = 'YOURSITE';

// Enforce shield in all non-prod environments.
if ($settings['environment'] != ENVIRONMENT_PROD) {
  $config['shield.settings']['shield_enable'] = TRUE;

  // But bypass shield for CI and local environments.
  if ($settings['environment'] == ENVIRONMENT_CI || $settings['environment'] == ENVIRONMENT_LOCAL) {
    $config['shield.settings']['shield_enable'] = FALSE;
  }
}
