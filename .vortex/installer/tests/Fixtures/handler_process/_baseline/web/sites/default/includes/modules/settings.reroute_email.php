<?php

/**
 * @file
 * Reroute email settings.
 */

declare(strict_types=1);

use DrevOps\EnvironmentDetector\Environment;

// Default reroute email address and allowed list.
$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@star-wars.com';
$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@star-wars.com';

// Enable rerouting in all environments except local, ci, stage and prod.
// This covers dev and any custom environments (e.g., PR environments).
if (!in_array($settings['environment'], [
  Environment::LOCAL,
  Environment::CI,
  Environment::STAGE,
  Environment::PRODUCTION,
])) {
  $config['reroute_email.settings']['enable'] = TRUE;
}
else {
  $config['reroute_email.settings']['enable'] = FALSE;
}

// Allow to disable reroute email completely in the environment.
if (!empty(getenv('DRUPAL_REROUTE_EMAIL_DISABLED'))) {
  $config['reroute_email.settings']['enable'] = FALSE;
}
