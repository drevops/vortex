<?php

/**
 * @file
 * Redis configuration.
 *
 * Redis module can work with Redis or Valkey services as they are
 * interchangeable. We use `DRUPAL_REDIS_` environment variables as the Drupal
 * module name is `redis`.
 *
 * @phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
 * @phpcs:disable Drupal.Commenting.InlineComment.SpacingAfter
 * @phpcs:disable Drupal.Commenting.InlineComment.InvalidEndChar
 */

declare(strict_types=1);

// Using 'DRUPAL_REDIS_ENABLED' variable to resolve deployment concurrency:
// Redis module needs to be enabled without the configuration below applied
// while the Redis/Valkey service gets provisioned (deployment #1), then the
// cache needs to be switched to Redis/Valkey with setting
// 'DRUPAL_REDIS_ENABLED=1' for environments and triggering another deployment
// (deployment #2) to get that env variable applied.
// Once all environments were redeployed twice, the 'DRUPAL_REDIS_ENABLED=1'
// can be set for all environments as a per-project variable and per-env
// variables would need to be removed. The next deployment (#3) would use
// project-wide env variable (and since it has the same value '1' as removed
// per-env variable - there will be no change in how code works).
if (file_exists($contrib_path . '/redis') && !empty(getenv('DRUPAL_REDIS_ENABLED'))) {
  // Some providers use `REDIS_`-prefixed environment variables.
  $settings['redis.connection']['host'] = getenv('VALKEY_HOST') ?: getenv('REDIS_HOST') ?: 'valkey';
  $settings['redis.connection']['port'] = getenv('VALKEY_SERVICE_PORT') ?: getenv('REDIS_SERVICE_PORT') ?: '6379';

  // Customize used interface.
  $settings['redis.connection']['interface'] = 'PhpRedis';

  // Do not set the cache backend during installations of Drupal, but allow
  // to override this by setting VORTEX_REDIS_EXTENSION_LOADED to non-zero.
  // Note that Valkey uses `redis` PHP extension.
  if ((extension_loaded('redis') && getenv('VORTEX_REDIS_EXTENSION_LOADED') === FALSE) || !empty(getenv('VORTEX_REDIS_EXTENSION_LOADED'))) {

    // Set Redis as the default backend for any cache bin not otherwise
    // specified.
    $settings['cache']['default'] = 'cache.backend.redis';

    // Per-bin configuration examples, bypass the default ChainedFastBackend.
    // *Only* use this when using Relay (see README.Relay.md) or when APCu is
    // not available.
    // $settings['cache']['bins']['config'] = 'cache.backend.redis';
    // $settings['cache']['bins']['discovery'] = 'cache.backend.redis';
    // $settings['cache']['bins']['bootstrap'] = 'cache.backend.redis';

    // Use compression for cache entries longer than the specified limit.
    $settings['redis_compress_length'] = 100;

    // Customize the prefix, a reliable but long fallback is used if not
    // defined.
    // $settings['cache_prefix'] = 'prefix';

    // Respect specific TTL with an offset see README.md for more information.
    $settings['redis_ttl_offset'] = 3600;

    // Additional optimizations, see README.md.
    $settings['redis_invalidate_all_as_delete'] = TRUE;

    $settings['flush_redis_on_drupal_flush_cache'] = TRUE;

    // Apply changes to the container configuration to better leverage Redis.
    // This includes using Redis for the lock and flood control systems, as well
    // as the cache tag checksum. Alternatively, copy the contents of that file
    // to your project-specific services.yml file, modify as appropriate, and
    // remove this line.
    $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

    // Allow the services to work before the Redis module itself is enabled.
    $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';

    if (!isset($class_loader)) {
      // Initialize the autoloader.
      $class_loader = require_once $app_root . '/autoload.php';
      if ($class_loader === TRUE) {
        $class_loader = require $app_root . '/autoload.php';
      }
    }

    // Manually add the classloader path, this is required for the container
    // cache bin definition below and allows to use it without the redis module
    // being enabled.
    $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');

    // Use redis for container cache.
    // The container cache is used to load the container definition itself, and
    // thus any configuration stored in the container itself is not available
    // yet. These lines force the container cache to use Redis rather than the
    // default SQL cache.
    $settings['bootstrap_container_definition'] = [
      'parameters' => [],
      'services' => [
        'redis.factory' => [
          'class' => 'Drupal\redis\ClientFactory',
        ],
        'cache.backend.redis' => [
          'class' => 'Drupal\redis\Cache\CacheBackendFactory',
          'arguments' => ['@redis.factory', '@cache_tags_provider.container', '@serialization.phpserialize'],
        ],
        'cache.container' => [
          'class' => '\Drupal\redis\Cache\PhpRedis',
          'factory' => ['@cache.backend.redis', 'get'],
          'arguments' => ['container'],
        ],
        'cache_tags_provider.container' => [
          'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
          'arguments' => ['@redis.factory'],
        ],
        'serialization.phpserialize' => [
          'class' => 'Drupal\Component\Serialization\PhpSerialize',
        ],
      ],
    ];
  }
}
