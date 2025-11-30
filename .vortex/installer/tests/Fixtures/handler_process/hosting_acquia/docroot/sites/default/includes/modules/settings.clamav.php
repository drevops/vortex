<?php

/**
 * @file
 * ClamAV settings.
 */

declare(strict_types=1);

if (file_exists($contrib_path . '/clamav') && !empty(getenv('DRUPAL_CLAMAV_ENABLED'))) {
  $clamav_mode = getenv('DRUPAL_CLAMAV_MODE') ?: NULL;
  if (in_array(strtolower((string) $clamav_mode), ['0', 'daemon'])) {
    // Drupal\clamav\Config::MODE_DAEMON.
    $config['clamav.settings']['scan_mode'] = 0;
    $config['clamav.settings']['mode_daemon_tcpip']['hostname'] = getenv('CLAMAV_HOST') ?: 'clamav';
    $config['clamav.settings']['mode_daemon_tcpip']['port'] = getenv('CLAMAV_PORT') ?: 3310;
  }
  else {
    // Drupal\clamav\Config::MODE_EXECUTABLE.
    $config['clamav.settings']['scan_mode'] = 1;
    $config['clamav.settings']['executable_path'] = '/usr/bin/clamscan';
  }
}
