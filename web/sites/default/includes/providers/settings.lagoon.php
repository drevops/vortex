<?php

/**
 * @file
 * Lagoon hosting provider settings.
 */

declare(strict_types=1);

if (getenv('LAGOON') && getenv('LAGOON_ENVIRONMENT_TYPE') == 'production' || getenv('LAGOON_ENVIRONMENT_TYPE') == 'development') {
  // Do not put any Lagoon-specific settings in this code block. It is used
  // to explicitly map Lagoon environments to $settings['environment']
  // variable only.
  // Instead, use 'PER-ENVIRONMENT SETTINGS' section below.
  //
  // Environment is marked as 'production' in Lagoon.
  if (getenv('LAGOON_ENVIRONMENT_TYPE') == 'production') {
    $settings['environment'] = ENVIRONMENT_PROD;
  }
  // All other environments running in Lagoon are considered 'development'.
  else {
    // Any other environment is considered 'development' in Lagoon.
    $settings['environment'] = ENVIRONMENT_DEV;

    // But try to identify production environment using a branch name for
    // the cases when 'production' Lagoon environment is not provisioned yet.
    if (!empty(getenv('LAGOON_GIT_BRANCH')) && !empty(getenv('DREVOPS_LAGOON_PRODUCTION_BRANCH')) && getenv('LAGOON_GIT_BRANCH') === getenv('DREVOPS_LAGOON_PRODUCTION_BRANCH')) {
      $settings['environment'] = ENVIRONMENT_PROD;
    }
    // Dedicated test environment based on a branch name.
    elseif (getenv('LAGOON_GIT_BRANCH') == 'master') {
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
  $settings['cache_prefix']['default'] = (getenv('LAGOON_PROJECT') ?: getenv('DREVOPS_PROJECT')) . '_' . (getenv('LAGOON_GIT_SAFE_BRANCH') ?: getenv('DREVOPS_LAGOON_PRODUCTION_BRANCH'));

  // Trusted host patterns for Lagoon internal routes.
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
