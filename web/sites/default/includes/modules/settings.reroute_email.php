<?php

/**
 * @file
 * Reroute email settings.
 */

declare(strict_types=1);

$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@your-site-domain.example';
$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@your-site-domain.example';

// Enable rerouting in all environments except local, stage and prod.
// This covers ci, dev and any custom environments (e.g., PR environments).
if (!in_array($settings['environment'], [ENVIRONMENT_LOCAL, ENVIRONMENT_STAGE, ENVIRONMENT_PROD], TRUE)) {
  $config['reroute_email.settings']['enable'] = TRUE;
}
else {
  $config['reroute_email.settings']['enable'] = FALSE;
}

if ($settings['environment'] === ENVIRONMENT_CI) {
  // An empty address aborts delivery instead of forwarding the message, and an
  // empty allowlist leaves no recipient able to bypass the interception.
  $config['reroute_email.settings']['address'] = '';
  $config['reroute_email.settings']['allowed'] = '';
  // The interception notice would otherwise render on every page that sends
  // mail during a test run.
  $config['reroute_email.settings']['message'] = FALSE;
}

// Allow disabling reroute email completely in an environment.
if (!empty(getenv('DRUPAL_REROUTE_EMAIL_DISABLED'))) {
  $config['reroute_email.settings']['enable'] = FALSE;
}
