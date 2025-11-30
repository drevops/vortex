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

$vortex_localdev_url = getenv('VORTEX_LOCALDEV_URL');
if (!empty($vortex_localdev_url)) {
  // Local development URL.
  $patterns = str_replace(['.', 'https://', 'http://', ','], [
    '\.', '', '', '|',
  ], $vortex_localdev_url);
  $settings['trusted_host_patterns'][] = '^' . $patterns . '$';

  // URL for internal container access (e.g., via drush, in tests etc.).
  $settings['trusted_host_patterns'][] = '^nginx$';
}
