<?php

/**
 * @file
 * Seckit settings.
 */

declare(strict_types=1);

use DrevOps\EnvironmentDetector\Environment;

if ($settings['environment'] === Environment::CI || $settings['environment'] === Environment::LOCAL) {
  // Disable CSP locally and in CI as we do not serve the site over HTTPS.
  $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
  $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
}
