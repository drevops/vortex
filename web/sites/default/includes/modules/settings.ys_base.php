<?php

/**
 * @file
 * Settings for the YOURSITE Base module.
 */

declare(strict_types=1);

use DrevOps\EnvironmentDetector\Environment;

if ($settings['environment'] === Environment::CI) {
  // Disable mail send out.
  $settings['suspend_mail_send'] = TRUE;
}
