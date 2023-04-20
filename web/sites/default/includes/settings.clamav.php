<?php

/**
 * @file
 * ClamAV settings.
 */

if (file_exists($contrib_path . '/clamav') && !empty(getenv('DREVOPS_CLAMAV_ENABLED'))) {
  $clamav_mode = getenv('CLAMAV_MODE');
  if ($clamav_mode === 0 || strtolower($clamav_mode) == 'daemon') {
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
