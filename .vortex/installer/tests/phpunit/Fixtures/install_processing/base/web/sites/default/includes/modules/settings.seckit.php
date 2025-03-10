<?php

/**
 * @file
 * Seckit settings.
 */

declare(strict_types=1);

if ($settings['environment'] == ENVIRONMENT_CI || $settings['environment'] == ENVIRONMENT_LOCAL) {
  // Disable SCP locally and in CI as we do not serve the site over HTTPS.
  $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
}
