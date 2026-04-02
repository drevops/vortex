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
  $ah_site_group = getenv('AH_SITE_GROUP');
  $ah_site_env = getenv('AH_SITE_ENVIRONMENT');

  // Delay the initial database connection.
  $config['acquia_hosting_settings_autoconnect'] = FALSE;

  // Include Acquia environment settings.
  if (!empty($ah_site_group)) {
    $ah_settings_file = getenv('DRUPAL_ACQUIA_SETTINGS_FILE') ?: sprintf('/var/www/site-php/%s/%s-settings.inc', $ah_site_group, $ah_site_group);
    // @codeCoverageIgnoreStart
    if (!file_exists($ah_settings_file)) {
      throw new \RuntimeException(sprintf('Acquia settings file "%s" not found. Check Acquia Cloud environment configuration.', $ah_settings_file));
    }
    require $ah_settings_file;
    // @codeCoverageIgnoreEnd
  }

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
  $config_path = getenv('DRUPAL_CONFIG_PATH');
  if (!empty($config_path)) {
    $settings['config_sync_directory'] = $config_path;
  }
  elseif (!empty($settings['config_vcs_directory'])) {
    $settings['config_sync_directory'] = $settings['config_vcs_directory'];
  }

  // Automatically create an Apache HTTP .htaccess file in writable directories.
  $settings['auto_create_htaccess'] = TRUE;

  // Allow to override temporary path using per-head mounted directory or
  // DRUPAL_TMP_PATH variable.
  // @see https://docs.acquia.com/acquia-cloud-platform/manage-apps/files/temporary#section-important-considerations
  $settings['file_temp_path'] = '/tmp';

  if (!empty($ah_site_group) && getenv('DRUPAL_TMP_PATH_IS_SHARED')) {
    // @see https://acquia.my.site.com/s/article/360054835954-Bulk-Upload-Not-Working-Correctly
    $settings['file_temp_path'] = sprintf('/mnt/gfs/%s.%s/tmp', $ah_site_group, $ah_site_env);
  }

  if (getenv('DRUPAL_TMP_PATH')) {
    $settings['file_temp_path'] = getenv('DRUPAL_TMP_PATH');
  }
}
