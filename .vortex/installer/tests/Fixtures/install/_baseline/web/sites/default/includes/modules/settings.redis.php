<?php

/**
 * @file
 * Valkey configuration.
 */

declare(strict_types=1);

use Drupal\Component\Serialization\PhpSerialize;
use Drupal\redis\Cache\CacheBackendFactory;
use Drupal\redis\Cache\PhpRedis;
use Drupal\redis\Cache\RedisCacheTagsChecksum;
use Drupal\redis\ClientFactory;

// Using 'DRUPAL_VALKEY_ENABLED' variable to resolve deployment concurrency:
// Redis module needs to be enabled without the configuration below applied
// while the Valkey service gets provisioned (deployment #1), then the cache
// needs to be switched to Valkey with setting 'DRUPAL_VALKEY_ENABLED=1' for
// environments and triggering another deployment (deployment #2) to get that
// env variable applied.
// Once all environments were redeployed twice, the 'DRUPAL_VALKEY_ENABLED=1'
// can be set for all environments as a per-project variable and per-env
// variables would need to be removed. The next deployment (#3) would use
// project-wide env variable (and since it has the same value '1' as removed
// per-env variable - there will be no change in how code works).
if (file_exists($contrib_path . '/redis') && !empty(getenv('DRUPAL_VALKEY_ENABLED'))) {
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = getenv('VALKEY_HOST') ?: 'valkey';
  $settings['redis.connection']['port'] = getenv('VALKEY_SERVICE_PORT') ?: '6379';

  // Do not set the cache during installations of Drupal, but allow
  // to override this by setting VORTEX_VALKEY_EXTENSION_LOADED to non-zero.
  if ((extension_loaded('redis') && getenv('VORTEX_VALKEY_EXTENSION_LOADED') === FALSE) || !empty(getenv('VORTEX_VALKEY_EXTENSION_LOADED'))) {
    $settings['cache']['default'] = 'cache.backend.redis';

    if (!isset($class_loader)) {
      // Initialize the autoloader.
      $class_loader = require_once $app_root . '/autoload.php';
      if ($class_loader === TRUE) {
        $class_loader = require $app_root . '/autoload.php';
      }
    }

    $class_loader->addPsr4('Drupal\\redis\\', $contrib_path . '/redis/src');

    $settings['bootstrap_container_definition'] = [
      'parameters' => [],
      'services' => [
        'redis.factory' => [
          'class' => ClientFactory::class,
        ],
        'cache.backend.redis' => [
          'class' => CacheBackendFactory::class,
          'arguments' => [
            '@redis.factory',
            '@cache_tags_provider.container',
            '@serialization.phpserialize',
          ],
        ],
        'cache.container' => [
          'class' => PhpRedis::class,
          'factory' => ['@cache.backend.redis', 'get'],
          'arguments' => ['container'],
        ],
        'cache_tags_provider.container' => [
          'class' => RedisCacheTagsChecksum::class,
          'arguments' => ['@redis.factory'],
        ],
        'serialization.phpserialize' => [
          'class' => PhpSerialize::class,
        ],
      ],
    ];
  }
}
