<?php

/**
 * @file
 * Reroute email settings.
 */

declare(strict_types=1);

use DrevOps\EnvironmentDetector\Environment;

// Default reroute email address and allowed list.
$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@your-site-domain.example';
$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@your-site-domain.example';

// Enable rerouting in all environments except local, ci, stage and prod.
// This covers dev and any custom environments (e.g., PR environments).
if (!in_array($settings['environment'], [
  Environment::LOCAL,
  Environment::CI,
  Environment::STAGE,
  Environment::PRODUCTION,
])) {
  // Send every outgoing message to the address above instead of to its
  // intended recipient, unless that recipient matches the allowed list.
  $config['reroute_email.settings']['enable'] = TRUE;
}
else {
  // Deliver every message to its intended recipient.
  $config['reroute_email.settings']['enable'] = FALSE;
}

// Allow an environment to opt out of the rerouting set above.
if (!empty(getenv('DRUPAL_REROUTE_EMAIL_DISABLED'))) {
  // Deliver every message to its intended recipient.
  $config['reroute_email.settings']['enable'] = FALSE;
}
