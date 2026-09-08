<?php

/**
 * @file
 * Reroute email settings.
 */

declare(strict_types=1);

$settings['config_exclude_modules'][] = 'reroute_email';

$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@star-wars.com';
$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@star-wars.com';

if (in_array($settings['environment'], [ENVIRONMENT_LOCAL, ENVIRONMENT_CI, ENVIRONMENT_PROD], TRUE)) {
  // Deliver every message to its intended recipient. Local and CI capture
  // outgoing mail by other means, and production sends to real recipients.
  $config['reroute_email.settings']['enable'] = FALSE;
}
else {
  // Send every outgoing message to the address above instead of to its
  // intended recipient, unless that recipient matches the allowed list.
  $config['reroute_email.settings']['enable'] = TRUE;
}

// Allow an environment to opt out of the rerouting set above.
if (!empty(getenv('DRUPAL_REROUTE_EMAIL_DISABLED'))) {
  // Deliver every message to its intended recipient.
  $config['reroute_email.settings']['enable'] = FALSE;
}
