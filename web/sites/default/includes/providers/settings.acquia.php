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
  // The path is built dynamically from the AH_SITE_GROUP environment variable
  // provided by Acquia Cloud.
  $ah_site_group = getenv('AH_SITE_GROUP');
  // @codeCoverageIgnoreStart
  if (!empty($ah_site_group)) {
    $ah_settings_file = sprintf('/var/www/site-php/%s/%s-settings.inc', $ah_site_group, $ah_site_group);
    if (!file_exists($ah_settings_file)) {
      throw new \RuntimeException(sprintf('Acquia settings file "%s" not found. Check Acquia Cloud environment configuration.', $ah_settings_file));
    }
    require $ah_settings_file;
  }
  // @codeCoverageIgnoreEnd
  // Default all environments to 'dev', including ODE environments.
  $settings['environment'] = ENVIRONMENT_DEV;

  // Do not put any Acquia-specific settings in this code block. It is used
  // to explicitly map Acquia environments to $settings['environment']
  // variable only.
  switch (getenv('AH_SITE_ENVIRONMENT')) {
    case 'prod':
      $settings['environment'] = ENVIRONMENT_PROD;
      break;

    case 'stage':
    case 'test':
      $settings['environment'] = ENVIRONMENT_STAGE;
      break;
  }

  // Override the config sync directory with the DRUPAL_CONFIG_PATH environment
  // variable if provided, or fall back to the config_vcs_directory setting
  // provided by Acquia.
  $drupal_config_path = getenv('DRUPAL_CONFIG_PATH');
  if (!empty($drupal_config_path)) {
    $settings['config_sync_directory'] = $drupal_config_path;
  }
  elseif (!empty($settings['config_vcs_directory'])) {
    $settings['config_sync_directory'] = $settings['config_vcs_directory'];
  }

  // Automatically create an Apache HTTP .htaccess file in writable directories.
  $settings['auto_create_htaccess'] = TRUE;
}
