<?php

/**
 * @file
 * Settings for the YOURSITE Base module.
 */

declare(strict_types=1);

if ($settings['environment'] === ENVIRONMENT_CI) {
  $settings['suspend_mail_send'] = TRUE;
}
