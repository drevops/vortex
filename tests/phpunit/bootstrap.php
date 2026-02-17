<?php

/**
 * @file
 * PHPUnit bootstrap file.
 *
 * Prepares the environment for running PHPUnit tests.
 */

declare(strict_types=1);

// Set the browser test output base URL from the local development URL.
$localdev_url = getenv('VORTEX_LOCALDEV_URL');
if ($localdev_url) {
  $base_url = str_starts_with($localdev_url, 'http') ? $localdev_url : 'http://' . $localdev_url;
  putenv('BROWSERTEST_OUTPUT_BASE_URL=' . $base_url);
  $_SERVER['BROWSERTEST_OUTPUT_BASE_URL'] = $base_url;
  $_ENV['BROWSERTEST_OUTPUT_BASE_URL'] = $base_url;
}

// @see https://www.drupal.org/project/drupal/issues/2992069
$browser_output_dir = dirname(__DIR__, 2) . '/web/sites/simpletest/browser_output';
if (!is_dir($browser_output_dir)) {
  mkdir($browser_output_dir, 0775, TRUE);
}

// Load the Drupal core test bootstrap.
require dirname(__DIR__, 2) . '/web/core/tests/bootstrap.php';
