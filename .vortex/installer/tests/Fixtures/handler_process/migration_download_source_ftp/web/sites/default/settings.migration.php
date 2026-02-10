<?php

/**
 * @file
 * Migration settings.
 *
 * @todo Migration. Remove when migration configuration is no longer required.
 */

declare(strict_types=1);

// Migration DB settings.
$databases['migrate']['default'] = [
  'database' => getenv('DATABASE2_NAME') ?: getenv('DATABASE2_DATABASE') ?: 'drupal',
  'username' => getenv('DATABASE2_USERNAME') ?: 'drupal',
  'password' => getenv('DATABASE2_PASSWORD') ?: 'drupal',
  'host' => getenv('DATABASE2_HOST') ?: 'localhost',
  'port' => getenv('DATABASE2_PORT') ?: '',
  'prefix' => '',
  'driver' => 'mysql',
];
