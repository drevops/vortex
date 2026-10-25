<?php

/**
 * @file
 * Shield settings.
 */

declare(strict_types=1);

use DrevOps\EnvironmentDetector\Environment;

// Default Shield credentials.
//
// Shield can be enabled and disabled in production through UI.
//
// For other environments, Shield is enforced to be enabled.
// 'DRUPAL_SHIELD_USER' and 'DRUPAL_SHIELD_PASS' environment variables must
// be added in the environment or there will be no way to bypass HTTP Auth.
//
// Note that this approach is a bit different from the other settings, where
// we enable and disable features based on the presence of environment
// variables: we must not disable Shield even if the environment variables
// are not present.
//
// Enforce Shield in all non-prod environments.
if ($settings['environment'] !== Environment::PRODUCTION) {
  $config['shield.settings']['shield_enable'] = TRUE;

  // But bypass Shield for CI and local environments.
  if ($settings['environment'] === Environment::CI || $settings['environment'] === Environment::LOCAL) {
    $config['shield.settings']['shield_enable'] = FALSE;
  }
}

// Set credentials, but only if the environment variables are present.
$shield_user = getenv('DRUPAL_SHIELD_USER');
$shield_pass = getenv('DRUPAL_SHIELD_PASS');
if (!empty($shield_user) && !empty($shield_pass)) {
  $config['shield.settings']['credentials']['shield']['user'] = $shield_user;
  $config['shield.settings']['credentials']['shield']['pass'] = $shield_pass;
}

// Allow to override the title of the shield pop-up.
$shield_print = getenv('DRUPAL_SHIELD_PRINT');
if (!empty($shield_print)) {
  $config['shield.settings']['print'] = $shield_print;
}

// Allow to disable Shield completely in the environment.
if (getenv('DRUPAL_SHIELD_DISABLED') === '1') {
  $config['shield.settings']['shield_enable'] = FALSE;
}

// Allow ACME challenge path for Let's Encrypt certificate generation.
if (getenv('DRUPAL_SHIELD_ALLOW_ACME_CHALLENGE') === '1') {
  $config['shield.settings']['method'] = 0;
  $shield_acme_path = '/.well-known/acme-challenge/*';
  $shield_existing_paths = $config['shield.settings']['paths'] ?? '';
  $config['shield.settings']['paths'] = str_contains($shield_existing_paths, $shield_acme_path) ? $shield_existing_paths : trim($shield_existing_paths . "\n" . $shield_acme_path);
}
