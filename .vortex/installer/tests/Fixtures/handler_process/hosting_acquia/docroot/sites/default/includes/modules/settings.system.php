<?php

/**
 * @file
 * System module settings.
 */

declare(strict_types=1);

// Expiration of cached pages.
$config['system.performance']['cache']['page']['max_age'] = (int) (getenv('DRUPAL_CACHE_PAGE_MAX_AGE') ?: 900);

if ($settings['environment'] === ENVIRONMENT_PROD) {
  // Always aggregate CSS and JS files in production.
  $config['system.performance']['css']['preprocess'] = TRUE;
  $config['system.performance']['js']['preprocess'] = TRUE;
}

if ($settings['environment'] === ENVIRONMENT_LOCAL || $settings['environment'] === ENVIRONMENT_CI) {
  // Never harden permissions on sites/default/files.
  $settings['skip_permissions_hardening'] = TRUE;
  // Show all error messages on the site.
  $config['system.logging']['error_level'] = 'all';
}

if ($settings['environment'] === ENVIRONMENT_CI) {
  // Store outgoing messages in the state system instead of handing them to the
  // mail transport, so that tests can assert on the messages that were
  // produced. Being a settings override, this cannot be switched off from
  // within the site, including by a test that does not ask for a mail
  // collector. A message routed through a module-specific interface still
  // reaches the transport, which the container points at a closed port.
  $config['system.mail']['interface']['default'] = 'test_mail_collector';
}
