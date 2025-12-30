<?php

/**
 * @file
 * Acquia hosting provider settings.
 *
 * Do not place any custom settings in this file.
 * It is used to explicitly map provider environments to
 * $settings['environment'] and set platform-specific settings only.
 * Instead, use per-module settings files.
 *
 * @see https://docs.acquia.com/acquia-cloud/develop/env-variable
 */

declare(strict_types=1);

if (!empty(getenv('AH_SITE_ENVIRONMENT'))) {
  // Delay the initial database connection.
  $config['acquia_hosting_settings_autoconnect'] = FALSE;

  // Include Acquia environment settings.
  if (file_exists('/var/www/site-php/your_site/your_site-settings.inc')) {
    // @codeCoverageIgnoreStart
    // @phpstan-ignore-next-line
    require '/var/www/site-php/your_site/your_site-settings.inc';
    // @codeCoverageIgnoreEnd
  }

  // Set the config sync directory to a `config_vcs_directory`, but still
  // allow overriding it with the DRUPAL_CONFIG_PATH environment variable.
  if (!empty($settings['config_vcs_directory'])) {
    $settings['config_sync_directory'] = getenv('DRUPAL_CONFIG_PATH') ?: $settings['config_vcs_directory'];
  }

  // Automatically create an Apache HTTP .htaccess file in writable directories.
  $settings['auto_create_htaccess'] = TRUE;

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

    case 'stage':
    case 'test':
      $settings['environment'] = ENVIRONMENT_STAGE;
      break;
  }
}
