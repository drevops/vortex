@@ -172,33 +172,3 @@
 ```
 
 A `type` of `ALL` with a large `rows` value is a full table scan. `Using filesort` on a large result means the `ORDER BY` column has no usable index.
-
-## Redis probe
-
-Confirms which backend the cache bins use and prints the Redis memory and hit statistics.
-
-```php
-<?php
-
-/**
- * @file
- * Prints the cache backend of the main bins and the Redis statistics.
- */
-
-declare(strict_types=1);
-
-use Drupal\redis\ClientFactory;
-
-foreach (['render', 'dynamic_page_cache', 'default'] as $bin) {
-  printf("cache.%s: %s\n", $bin, get_class(\Drupal::cache($bin)));
-}
-
-$info = ClientFactory::getClient()->info();
-foreach (['used_memory_human', 'maxmemory_human', 'maxmemory_policy', 'evicted_keys', 'keyspace_hits', 'keyspace_misses'] as $key) {
-  printf("%s: %s\n", $key, $info[$key] ?? 'n/a');
-}
-```
-
-- `Drupal\redis\Cache\PhpRedis` is the expected backend. `Drupal\Core\Cache\DatabaseBackend` means `settings.redis.php` didn't apply.
-- A growing `evicted_keys` value means the instance is full. Pages then fall out of the cache and render cold again.
-- The command line and PHP-FPM read the same settings files, but they run in different containers. When the result here disagrees with what web requests show, check the flag in the `cli` container with `ahoy cli printenv DRUPAL_REDIS_ENABLED`, and check that `/admin/reports/redis` reports a connected client for web requests.
