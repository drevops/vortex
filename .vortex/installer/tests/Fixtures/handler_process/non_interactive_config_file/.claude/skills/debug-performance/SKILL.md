@@ -98,17 +98,6 @@
 - Custom data cached by the code needs its own tag, invalidated through the `cache_tags.invalidator` service when the data changes.
 - Avoid `ahoy drush cache:rebuild` as a fix. It hides the missing tag until the next edit.
 
-## Redis
-
-The `redis` service in `docker-compose.yml` runs a Redis-compatible Valkey server. `web/sites/default/includes/modules/settings.redis.php` makes it the default cache backend when the module's code is on disk and `DRUPAL_REDIS_ENABLED` is exactly `1`, which the shipped `.env` sets. The backend switch also needs the `redis` PHP extension, or `VORTEX_REDIS_EXTENSION_LOADED` set to `1`. The file also loads the module's `example.services.yml`, which moves locks, flood control and cache tag checksums to Redis, and sets `flush_redis_on_drupal_flush_cache`, so `ahoy drush cache:rebuild` flushes Redis too.
-
-- Confirm the backend with the Redis probe in `references/probes.md`. The `render` bin should report `Drupal\redis\Cache\PhpRedis`. A database backend means the flag or the extension is missing, and every cache read is a SQL query.
-- Read `evicted_keys` and the hit and miss counts from the same probe. Evictions on a full instance make warm pages render cold again, so compare `used_memory_human` with `maxmemory_human` before blaming code.
-- Open `/admin/reports/redis`. It lists keys per cache bin, the render cache entries with the most variations, which points at a high-cardinality context, and the most invalidated cache tags, which points at an invalidation storm.
-- Clear Redis on its own with `ahoy flush-redis`, to separate an eviction problem from a render that is slow on a clean cache.
-- Change Redis settings in `settings.redis.php` only. It holds commented per-bin examples; follow its note before moving the `config`, `discovery` or `bootstrap` bins to Redis.
-- [Redis](https://www.vortextemplate.com/docs/development/modules/contributed-modules#redis) lists the variables the settings file reads.
-
 ## Web server and request handling
 
 - Nginx runs from the `uselagoon/nginx-drupal` image, built by `.docker/nginx-drupal.dockerfile`. The project's own configuration lives in `.docker/config/nginx/`: the Dockerfile copies `redirects-map.conf` to `/etc/nginx/redirects-map.conf` and each other file into `/etc/nginx/conf.d/drupal/` with a `server_prepend-` or `location_append-` prefix.
