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
$trusted_hosts_value = getenv('DRUPAL_TRUSTED_HOSTS');
if (!empty($trusted_hosts_value)) {
  $trusted_hosts_domains = array_map(trim(...), explode(',', $trusted_hosts_value));
  foreach ($trusted_hosts_domains as $trusted_host_domain) {
    if (!empty($trusted_host_domain)) {
      $trusted_host_domain = strtolower($trusted_host_domain);
      $trusted_hosts_escaped = preg_quote($trusted_host_domain, '/');
      $settings['trusted_host_patterns'][] = '^' . $trusted_hosts_escaped . '$';
    }
  }
}
