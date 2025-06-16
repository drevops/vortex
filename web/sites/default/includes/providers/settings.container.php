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

if (!empty(getenv('VORTEX_LOCALDEV_URL'))) {
  // Local development URL.
  $patterns = str_replace(['.', 'https://', 'http://', ','], [
    '\.', '', '', '|',
  ], getenv('VORTEX_LOCALDEV_URL'));
  $settings['trusted_host_patterns'][] = '^' . $patterns . '$';

  // URL when accessed from Behat tests.
  $settings['trusted_host_patterns'][] = '^nginx$';
}
