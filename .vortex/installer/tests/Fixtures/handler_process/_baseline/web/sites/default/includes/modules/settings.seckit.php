<?php

/**
 * @file
 * Seckit settings.
 */

declare(strict_types=1);

if ($settings['environment'] === ENVIRONMENT_LOCAL || $settings['environment'] === ENVIRONMENT_CI) {
  // The site is not served over HTTPS locally and in CI, so CSP is disabled.
  $config['seckit.settings']['seckit_xss']['csp']['checkbox'] = FALSE;
  $config['seckit.settings']['seckit_xss']['csp']['upgrade-req'] = FALSE;
}
