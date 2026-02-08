<?php

/**
 * @file
 * Shield settings.
 */

declare(strict_types=1);

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
if ($settings['environment'] !== ENVIRONMENT_PROD) {
  $config['shield.settings']['shield_enable'] = TRUE;

  // But bypass Shield for CI and local environments.
  if ($settings['environment'] === ENVIRONMENT_CI || $settings['environment'] === ENVIRONMENT_LOCAL) {
    $config['shield.settings']['shield_enable'] = FALSE;
  }
}

// Set credentials, but only if the environment variables are present.
if (!empty(getenv('DRUPAL_SHIELD_USER')) && !empty(getenv('DRUPAL_SHIELD_PASS'))) {
  $config['shield.settings']['credentials']['shield']['user'] = getenv('DRUPAL_SHIELD_USER');
  $config['shield.settings']['credentials']['shield']['pass'] = getenv('DRUPAL_SHIELD_PASS');
}

// Allow to override the title of the shield pop-up.
if (getenv('DRUPAL_SHIELD_PRINT')) {
  $config['shield.settings']['print'] = getenv('DRUPAL_SHIELD_PRINT');
}

// Allow to disable Shield completely in the environment.
if (!empty(getenv('DRUPAL_SHIELD_DISABLED'))) {
  $config['shield.settings']['shield_enable'] = FALSE;
}

// Allow ACME challenge path for Let's Encrypt certificate generation.
if (!empty(getenv('DRUPAL_SHIELD_ALLOW_ACME_CHALLENGE'))) {
  $config['shield.settings']['method'] = 0;
  $config['shield.settings']['paths'] = '/.well-known/acme-challenge/*';
}
