<?php

/**
 * @file
 * Lagoon hosting provider settings.
 *
 * Do not place any custom settings in this file.
 * It is used to explicitly map Lagoon environments to $settings['environment']
 * and set platform-specific settings only.
 * Instead, use per-module settings files.
 */

declare(strict_types=1);

if (!empty(getenv('LAGOON_KUBERNETES'))) {
  // Environment is marked as 'production' in Lagoon.
  if (getenv('LAGOON_ENVIRONMENT_TYPE') == 'production') {
    $settings['environment'] = ENVIRONMENT_PROD;
  }
  else {
    // All other environments running in Lagoon are considered 'development'.
    $settings['environment'] = ENVIRONMENT_DEV;

    // Try to identify production environment using a branch name for
    // the cases when the Lagoon environment is not marked as 'production' yet.
    if (!empty(getenv('LAGOON_GIT_BRANCH')) && !empty(getenv('VORTEX_LAGOON_PRODUCTION_BRANCH')) && getenv('LAGOON_GIT_BRANCH') === getenv('VORTEX_LAGOON_PRODUCTION_BRANCH')) {
      $settings['environment'] = ENVIRONMENT_PROD;
    }
    // Dedicated test environment based on a branch name.
    elseif (getenv('LAGOON_GIT_BRANCH') == 'main' || getenv('LAGOON_GIT_BRANCH') == 'master') {
      $settings['environment'] = ENVIRONMENT_TEST;
    }
    // Test environment based on a branch prefix for release and
    // hotfix branches.
    elseif (!empty(getenv('LAGOON_GIT_BRANCH')) && (str_starts_with(getenv('LAGOON_GIT_BRANCH'), 'release/') || str_starts_with(getenv('LAGOON_GIT_BRANCH'), 'hotfix/'))) {
      $settings['environment'] = ENVIRONMENT_TEST;
    }
  }

  // Lagoon version.
  if (!defined('LAGOON_VERSION')) {
    define('LAGOON_VERSION', '1');
  }

  // Lagoon reverse proxy settings.
  $settings['reverse_proxy'] = TRUE;
  // Reverse proxy settings.
  $settings['reverse_proxy_header'] = 'HTTP_TRUE_CLIENT_IP';

  // Cache prefix.
  $settings['cache_prefix']['default'] = (getenv('LAGOON_PROJECT') ?: getenv('VORTEX_PROJECT')) . '_' . (getenv('LAGOON_GIT_SAFE_BRANCH') ?: getenv('VORTEX_LAGOON_PRODUCTION_BRANCH'));

  // Trusted host patterns for Lagoon internal routes.
  // Do not modify this section. Instead, add your custom patterns to the
  // settings.php file.
  // URL when accessed from PHP processes in Lagoon.
  $settings['trusted_host_patterns'][] = '^nginx\-php$';
  // Lagoon URL.
  $settings['trusted_host_patterns'][] = '^.+\.au\.amazee\.io$';
  // Lagoon routes.
  if (getenv('LAGOON_ROUTES')) {
    $patterns = str_replace(['.', 'https://', 'http://', ','], [
      '\.', '', '', '|',
    ], getenv('LAGOON_ROUTES'));
    $settings['trusted_host_patterns'][] = '^' . $patterns . '$';
  }
}
