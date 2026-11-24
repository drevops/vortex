@@ -13,13 +13,13 @@
 
 Local defaults turn caching off, so measure nothing until these are checked:
 
-- When `web/sites/default/settings.local.php` exists, it may map the `render`, `page` and `dynamic_page_cache` bins to `cache.backend.null`, as `example.settings.local.php` does. Every request is then a MISS. For the investigation, comment out those 3 lines in `settings.local.php`, which Git ignores, and keep `services.local.yml` loaded: it sets `http.response.debug_cacheability_headers: true`, which adds the debug headers in step 2. Don't use `DRUPAL_SETTINGS_LOCAL_SKIP` for this, because it skips `services.local.yml` too.
+- When `docroot/sites/default/settings.local.php` exists, it may map the `render`, `page` and `dynamic_page_cache` bins to `cache.backend.null`, as `example.settings.local.php` does. Every request is then a MISS. For the investigation, comment out those 3 lines in `settings.local.php`, which Git ignores, and keep `services.local.yml` loaded: it sets `http.response.debug_cacheability_headers: true`, which adds the debug headers in step 2. Don't use `DRUPAL_SETTINGS_LOCAL_SKIP` for this, because it skips `services.local.yml` too.
 - Check **Do not cache markup** on `/admin/config/development/settings`. When it's on, Drupal disables the same 3 bins.
 
 - Run the request probe and confirm that no bin in its first lines reports `NullBackend`.
 - Turn Xdebug off with `ahoy up` if `ahoy info` shows it on. It slows every request.
 - Measure on a database with production-sized content. A page that is fast with 20 nodes can be slow with 20,000. Ask the developer before running `ahoy provision`, which replaces the local database.
-- Know what stays different: CSS and JavaScript aggregation is off locally (`example.settings.local.php`) and forced on in production (`web/sites/default/includes/modules/settings.system.php`), and the local database answers each query faster than a networked production database. Compare query counts, not local milliseconds.
+- Know what stays different: CSS and JavaScript aggregation is off locally (`example.settings.local.php`) and forced on in production (`docroot/sites/default/includes/modules/settings.system.php`), and the local database answers each query faster than a networked production database. Compare query counts, not local milliseconds.
 
 ## 2. Measure
 
@@ -53,7 +53,7 @@
 | `X-Drupal-Cache-Max-Age`, `-Contexts`, `-Tags` | Values | The response's bubbled cacheability, with the local debug headers only |
 | `Cache-Control` | `max-age=900, public` | Anonymous cacheable page. The value comes from `DRUPAL_CACHE_PAGE_MAX_AGE` in `settings.system.php`, 900 seconds by default |
 
-The `UNCACHEABLE (poor cacheability)` thresholds come from `renderer.config.auto_placeholder_conditions` in `web/sites/default/services.yml`.
+The `UNCACHEABLE (poor cacheability)` thresholds come from `renderer.config.auto_placeholder_conditions` in `docroot/sites/default/services.yml`.
 
 Hosted environments send no debug headers, so read `X-Drupal-Cache`, `X-Drupal-Dynamic-Cache` and `Cache-Control` there.
 
@@ -78,7 +78,7 @@
    - `'user'` or `'session'` in a `contexts` list
    - `getSession()`, `$_SESSION` and messenger calls on anonymous pages. A session makes Page Cache skip the request.
    - `\Drupal::request()` or `\Drupal::currentUser()` read in a preprocess function or a block `build()` without the matching context
-4. For an element the blocks probe can't isolate, turn on the renderer debug output: copy the whole `renderer.config` parameter from `web/sites/default/services.yml` into `services.local.yml`, set its `debug` key to `true`, and run `ahoy drush cache:rebuild`. The parameters merge shallowly, so a partial copy drops the other keys. The page source then wraps every render-cached element in `<!-- START RENDERER -->` comments with its `CACHE MAX-AGE`, `CACHE CONTEXTS` and `CACHE TAGS`.
+4. For an element the blocks probe can't isolate, turn on the renderer debug output: copy the whole `renderer.config` parameter from `docroot/sites/default/services.yml` into `services.local.yml`, set its `debug` key to `true`, and run `ahoy drush cache:rebuild`. The parameters merge shallowly, so a partial copy drops the other keys. The page source then wraps every render-cached element in `<!-- START RENDERER -->` comments with its `CACHE MAX-AGE`, `CACHE CONTEXTS` and `CACHE TAGS`.
 5. Fix the cause with the patterns in `references/fixes.md`. Per-user parts go behind a `#lazy_builder` placeholder, time-based content gets a real `max-age`, and contexts get narrower, such as `user.permissions` or `user.roles` instead of `user`.
 
 Page Cache ignores `max-age`: an anonymous page stays cached until a cache tag on it is invalidated. Time-limited anonymous content needs a tag that something invalidates on schedule, or the kill switch as a last resort.
@@ -100,7 +100,7 @@
 
 ## Redis
 
-The `redis` service in `docker-compose.yml` runs a Redis-compatible Valkey server. `web/sites/default/includes/modules/settings.redis.php` makes it the default cache backend when the module's code is on disk and `DRUPAL_REDIS_ENABLED` is exactly `1`, which the shipped `.env` sets. The backend switch also needs the `redis` PHP extension, or `VORTEX_REDIS_EXTENSION_LOADED` set to `1`. The file also loads the module's `example.services.yml`, which moves locks, flood control and cache tag checksums to Redis, and sets `flush_redis_on_drupal_flush_cache`, so `ahoy drush cache:rebuild` flushes Redis too.
+The `redis` service in `docker-compose.yml` runs a Redis-compatible Valkey server. `docroot/sites/default/includes/modules/settings.redis.php` makes it the default cache backend when the module's code is on disk and `DRUPAL_REDIS_ENABLED` is exactly `1`, which the shipped `.env` sets. The backend switch also needs the `redis` PHP extension, or `VORTEX_REDIS_EXTENSION_LOADED` set to `1`. The file also loads the module's `example.services.yml`, which moves locks, flood control and cache tag checksums to Redis, and sets `flush_redis_on_drupal_flush_cache`, so `ahoy drush cache:rebuild` flushes Redis too.
 
 - Confirm the backend with the Redis probe in `references/probes.md`. The `render` bin should report `Drupal\redis\Cache\PhpRedis`. A database backend means the flag or the extension is missing, and every cache read is a SQL query.
 - Read `evicted_keys` and the hit and miss counts from the same probe. Evictions on a full instance make warm pages render cold again, so compare `used_memory_human` with `maxmemory_human` before blaming code.
@@ -115,9 +115,9 @@
 - To change caching headers for static files, add a file to `.docker/config/nginx/`, add its `COPY` line to `.docker/nginx-drupal.dockerfile`, and rebuild the container with `ahoy up -- --build nginx`. Check the new headers with the `curl -D -` command from step 2.
 - A 503 with no `X-Drupal-*` headers comes from `maintenance.conf`, served while `VORTEX_WEBSERVER_MAINTENANCE_MODE` is `1`. It isn't a slow Drupal response.
 - Read the web server and PHP logs with `ahoy logs -- --tail=50 nginx` and `ahoy logs -- --tail=50 php`, and Drupal's log with `ahoy drush watchdog:show --count=20`.
-- In hosted environments, Automated Cron stays on; `web/sites/default/includes/modules/settings.automated_cron.php` turns it off only in `local` and `ci`. A regular spike in load can be cron, so check `ahoy drush watchdog:show --type=cron` before profiling a page.
+- In hosted environments, Automated Cron stays on; `docroot/sites/default/includes/modules/settings.automated_cron.php` turns it off only in `local` and `ci`. A regular spike in load can be cron, so check `ahoy drush watchdog:show --type=cron` before profiling a page.
 
-- Requests for missing static files with the extensions listed in `web/sites/default/includes/modules/settings.fast_404.php` get a 404 before Drupal bootstraps. Missing image style derivatives still reach Drupal, which generates them on first request.
+- Requests for missing static files with the extensions listed in `docroot/sites/default/includes/modules/settings.fast_404.php` get a 404 before Drupal bootstraps. Missing image style derivatives still reach Drupal, which generates them on first request.
 
 ## Devel
 
