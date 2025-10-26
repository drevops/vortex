<?php

/**
 * @file
 * Trusted host patterns settings.
 *
 * Provides custom domain support for trusted host patterns when CDN is in
 * front of Lagoon or when custom domains need to be explicitly trusted.
 */

declare(strict_types=1);

// Add custom domains to trusted host patterns if specified.
$trusted_hosts = getenv('DRUPAL_TRUSTED_HOSTS');
if (!empty($trusted_hosts)) {
  $domains = array_map(trim(...), explode(',', $trusted_hosts));
  foreach ($domains as $domain) {
    if (!empty($domain)) {
      $domain = strtolower($domain);
      $escaped_domain = preg_quote($domain, '/');
      $settings['trusted_host_patterns'][] = '^' . $escaped_domain . '$';
    }
  }
}
