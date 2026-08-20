<?php

/**
 * @file
 * Reroute email settings.
 */

declare(strict_types=1);

$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@your-site-domain.example';
$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@your-site-domain.example';

// Rerouting replaces the recipient of an outgoing message with the address
// above, unless that recipient matches the allowed list. Disabling it delivers
// every message to its original recipients, so it is disabled only where that
// delivery is either intended or already intercepted:
//
// - local: the mail catcher of the local stack receives the message.
// - ci: the mail collector configured in settings.system.php stores the message
//   instead of sending it, and preserves the original recipient so that tests
//   can assert on it.
// - stage: user acceptance testing needs messages to reach their recipients.
// - prod: messages must reach their recipients.
//
// Rerouting therefore covers dev and any custom environment, such as a
// per-pull-request environment, where recipients are real people and delivery
// is not wanted.
if (!in_array($settings['environment'], [ENVIRONMENT_LOCAL, ENVIRONMENT_CI, ENVIRONMENT_STAGE, ENVIRONMENT_PROD], TRUE)) {
  $config['reroute_email.settings']['enable'] = TRUE;
}
else {
  $config['reroute_email.settings']['enable'] = FALSE;
}

// Deliver messages to their original recipients in an environment where
// rerouting would otherwise apply.
if (!empty(getenv('DRUPAL_REROUTE_EMAIL_DISABLED'))) {
  $config['reroute_email.settings']['enable'] = FALSE;
}
