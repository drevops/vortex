<?php

/**
 * @file
 * Settings for the star wars Base module.
 */

declare(strict_types=1);

if ($settings['environment'] === ENVIRONMENT_CI) {
  // Disable mail send out.
  $settings['suspend_mail_send'] = TRUE;
}
