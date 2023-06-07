<?php

/**
 * @file
 * Stage file proxy settings.
 */

if ($settings['environment'] != ENVIRONMENT_PROD) {
  $origin = 'https://your-site-url.example/';

  if (!empty(getenv('DRUPAL_SHIELD_USER')) && !empty(getenv('DRUPAL_SHIELD_PASS'))) {
    $origin = sprintf('https://%s:%s@your-site-url.example/', getenv('DRUPAL_SHIELD_USER'), getenv('DRUPAL_SHIELD_PASS'));
  }
  $config['stage_file_proxy.settings']['origin'] = $origin;

  $config['stage_file_proxy.settings']['hotlink'] = FALSE;
}
