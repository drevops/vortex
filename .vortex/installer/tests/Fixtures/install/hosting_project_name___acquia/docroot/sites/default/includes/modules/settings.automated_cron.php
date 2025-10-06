<?php

/**
 * @file
 * Automated cron settings.
 */

declare(strict_types=1);

if ($settings['environment'] === ENVIRONMENT_LOCAL || $settings['environment'] === ENVIRONMENT_CI) {
  // Disable built-in cron trigger.
  $config['automated_cron.settings']['interval'] = 0;
}
