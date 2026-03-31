<?php

/**
 * @file
 * Stage file proxy settings.
 */

declare(strict_types=1);

$stage_file_proxy_origin = getenv('DRUPAL_STAGE_FILE_PROXY_ORIGIN');
if (!empty($stage_file_proxy_origin) && $settings['environment'] !== ENVIRONMENT_PROD) {
  $stage_file_proxy_user = getenv('DRUPAL_SHIELD_USER');
  $stage_file_proxy_pass = getenv('DRUPAL_SHIELD_PASS');
  if (!empty($stage_file_proxy_user) && !empty($stage_file_proxy_pass)) {
    $stage_file_proxy_origin = str_replace('https://', sprintf('https://%s:%s@', $stage_file_proxy_user, $stage_file_proxy_pass), $stage_file_proxy_origin);
  }

  $config['stage_file_proxy.settings']['origin'] = $stage_file_proxy_origin;
  $config['stage_file_proxy.settings']['hotlink'] = FALSE;
}
