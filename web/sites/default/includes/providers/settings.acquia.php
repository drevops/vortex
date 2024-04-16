<?php

/**
 * @file
 * Acquia hosting provider settings.
 *
 * @see https://docs.acquia.com/acquia-cloud/develop/env-variable
 */

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
