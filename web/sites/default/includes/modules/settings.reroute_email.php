<?php

/**
 * @file
 * Reroute email settings.
 */

declare(strict_types=1);

// Default reroute email address and allowed list.
$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@your-site-domain.example';
$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@your-site-domain.example';

// Enable rerouting in all environments except local, ci, stage and prod.
// This covers dev and any custom environments (e.g., PR environments).
if (!in_array($settings['environment'], [ENVIRONMENT_LOCAL, ENVIRONMENT_CI, ENVIRONMENT_STAGE, ENVIRONMENT_PROD])) {
  $config['reroute_email.settings']['enable'] = TRUE;
}
else {
  $config['reroute_email.settings']['enable'] = FALSE;
}

// Allow to disable reroute email completely in the environment.
if (!empty(getenv('DRUPAL_REROUTE_EMAIL_DISABLED'))) {
  $config['reroute_email.settings']['enable'] = FALSE;
}
