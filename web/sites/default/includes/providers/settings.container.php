<?php

/**
 * @file
 * Container provider settings.
 *
 * Do not place any custom settings in this file.
 * It is used to explicitly map provider environments to
 * $settings['environment'] and set platform-specific settings only.
 * Instead, use per-module settings files.
 */

declare(strict_types=1);

$container_localdev_url = getenv('LOCALDEV_URL');
if (!empty($container_localdev_url)) {
  $container_urls = array_map(trim(...), explode(',', $container_localdev_url));
  foreach ($container_urls as $container_url) {
    $container_host = strtolower(str_replace(['https://', 'http://'], '', $container_url));
    if (!empty($container_host)) {
      $settings['trusted_host_patterns'][] = '^' . preg_quote($container_host, '/') . '$';
    }
  }

  // URL for internal container access (e.g., via drush, in tests etc.).
  $settings['trusted_host_patterns'][] = '^nginx$';
}
