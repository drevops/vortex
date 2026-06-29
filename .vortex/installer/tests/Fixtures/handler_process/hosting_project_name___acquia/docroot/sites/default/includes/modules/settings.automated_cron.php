<?php

/**
 * @file
 * Automated cron settings.
 */

declare(strict_types=1);

use DrevOps\EnvironmentDetector\Environment;

if ($settings['environment'] === Environment::LOCAL || $settings['environment'] === Environment::CI) {
  // Disable built-in cron trigger.
  $config['automated_cron.settings']['interval'] = 0;
}
