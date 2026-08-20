<?php

/**
 * @file
 * Reroute email settings.
 */

declare(strict_types=1);

$config['reroute_email.settings']['address'] = getenv('DRUPAL_REROUTE_EMAIL_ADDRESS') ?: 'webmaster@your-site-domain.example';
$config['reroute_email.settings']['allowed'] = getenv('DRUPAL_REROUTE_EMAIL_ALLOWED') ?: '*@your-site-domain.example';

// TRUE sends every outgoing message to the address above instead of to its
// intended recipient, unless that recipient matches the allowed list.
// FALSE delivers every message to its intended recipient.
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
