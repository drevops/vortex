---
name: debug-performance
description: Use when a page on this site is slow, a response shows X-Drupal-Cache or X-Drupal-Dynamic-Cache as MISS or UNCACHEABLE on every request, content stays stale after an edit, a page renders per user when it shouldn't, a route runs too many database queries, or when asked to profile, speed up or fix caching.
---

# Debug performance

Find why a page is slow or uncached, fix the cause, and prove the fix with the same measurement that found it. Never change code before you have a number to compare against.

Read `AGENTS.md` first. Run every command from the project root, through `ahoy`, 1 command per call. Write ad-hoc PHP as a file under `.artifacts/tmp/` and run it with `ahoy drush php:script <path>`; never use `php:eval`. The probe scripts are in [references/probes.md](references/probes.md), and the fix patterns are in [references/fixes.md](references/fixes.md).

## 1. Make the local site cache like production

Local defaults turn caching off, so measure nothing until these are checked:

- When `web/sites/default/settings.local.php` exists, it may map the `render`, `page` and `dynamic_page_cache` bins to `cache.backend.null`, as `example.settings.local.php` does. Every request is then a MISS. For the investigation, comment out those 3 lines in `settings.local.php`, which Git ignores, and keep `services.local.yml` loaded: it sets `http.response.debug_cacheability_headers: true`, which adds the debug headers in step 2. Don't use `DRUPAL_SETTINGS_LOCAL_SKIP` for this, because it skips `services.local.yml` too.
- Check **Do not cache markup** on `/admin/config/development/settings`. When it's on, Drupal disables the same 3 bins.

- Run the request probe and confirm that no bin in its first lines reports `NullBackend`.
- Turn Xdebug off with `ahoy up` if `ahoy info` shows it on. It slows every request.
- Measure on a database with production-sized content. A page that is fast with 20 nodes can be slow with 20,000. Ask the developer before running `ahoy provision`, which replaces the local database.
- Know what stays different: CSS and JavaScript aggregation is off locally (`example.settings.local.php`) and forced on in production (`web/sites/default/includes/modules/settings.system.php`), and the local database answers each query faster than a networked production database. Compare query counts, not local milliseconds.

## 2. Measure

Get the site URL from `ahoy info`. Take every measurement twice: once cold, right after `ahoy drush cache:rebuild`, and once warm.

**Headers.** Fetch the page and print the response headers:

```shell
curl -k -s -o /dev/null -D - "SITE_URL/path"
```

**Time to first byte.**

```shell
curl -k -s -o /dev/null -w "%{http_code} %{time_starttransfer}\n" "SITE_URL/path"
```

**As a user.** Measure as a user with the role that is slow, not as user 1, who bypasses access checks. Get a login link with `ahoy drush user:login --uid=<uid> --no-browser`, then follow the cookie steps on [Debugging](https://www.vortextemplate.com/docs/development/environment/debugging#request-authenticated-pages-with-curl) and add `-b .data/cookies.txt` to the commands above.

**Queries.** Run the request probe from `references/probes.md`. It prints the time, the database queries split into cache and other queries, the most repeated query shapes, the top callers and the response cacheability. It runs from the command line, where Drupal bypasses Page Cache and Dynamic Page Cache, so it measures the render work those caches would store. Record the "other" query count per route: it is the number that grows with production latency.

Read the headers with this table:

| Header | Value | Meaning |
|---|---|---|
| `X-Drupal-Cache` | `HIT` or `MISS` | Page Cache, anonymous requests only, the whole response |
| `X-Drupal-Cache` | `UNCACHEABLE (request policy)` | The request has a session or isn't a `GET` or `HEAD` |
| `X-Drupal-Cache` | `UNCACHEABLE (response policy)` | Code denied caching, such as the `page_cache_kill_switch` service or a route with the `no_cache` option |
| `X-Drupal-Dynamic-Cache` | `HIT` or `MISS` | Dynamic Page Cache, every user, the page minus its placeholders |
| `X-Drupal-Dynamic-Cache` | `UNCACHEABLE (poor cacheability)` | `max-age` 0 or a `user` or `session` context reached the response |
| `X-Drupal-Cache-Max-Age`, `-Contexts`, `-Tags` | Values | The response's bubbled cacheability, with the local debug headers only |
| `Cache-Control` | `max-age=900, public` | Anonymous cacheable page. The value comes from `DRUPAL_CACHE_PAGE_MAX_AGE` in `settings.system.php`, 900 seconds by default |

The `UNCACHEABLE (poor cacheability)` thresholds come from `renderer.config.auto_placeholder_conditions` in `web/sites/default/services.yml`.

Hosted environments send no debug headers, so read `X-Drupal-Cache`, `X-Drupal-Dynamic-Cache` and `Cache-Control` there.

On `dev` and `stage`, Shield asks for credentials first. Add `-u "USER:PASS"` with the values of `DRUPAL_SHIELD_USER` and `DRUPAL_SHIELD_PASS` from that environment.

## 3. Decide which layer is slow

- **Slow first byte, high query count**: the render path. Go to step 4 for caching and step 5 for queries.
- **Slow first byte, low query count**: blocking work in PHP, such as an outbound HTTP request, a large file read or a heavy computation. Look for `http_client` calls in the render path first, then step through with Xdebug as [Debugging](https://www.vortextemplate.com/docs/development/environment/debugging#xdebug) describes.
- **Fast first byte, slow page**: the front end. Check asset sizes and render-blocking assets with aggregation on, not the PHP side.
- **Fast once warm, slow every few minutes**: invalidation. A tag that changes on every write, such as `node_list` on a busy site, empties every page that carries it. Step 6 covers it.

## 4. Find what breaks caching

Work from the response down to the element that caused it.

1. Read `X-Drupal-Cache-Max-Age` and `X-Drupal-Cache-Contexts` from step 2.
2. Run the blocks probe from `references/probes.md`. It renders every enabled block in the default theme on its own and flags each one with `max-age` 0 or a `user` or `session` context.
3. Search the custom modules and the custom theme for the usual causes:
   - `'max-age' => 0`, `setCacheMaxAge(0)` and `mergeCacheMaxAge(0)`
   - `page_cache_kill_switch`
   - `'user'` or `'session'` in a `contexts` list
   - `getSession()`, `$_SESSION` and messenger calls on anonymous pages. A session makes Page Cache skip the request.
   - `\Drupal::request()` or `\Drupal::currentUser()` read in a preprocess function or a block `build()` without the matching context
4. For an element the blocks probe can't isolate, turn on the renderer debug output: copy the whole `renderer.config` parameter from `web/sites/default/services.yml` into `services.local.yml`, set its `debug` key to `true`, and run `ahoy drush cache:rebuild`. The parameters merge shallowly, so a partial copy drops the other keys. The page source then wraps every render-cached element in `<!-- START RENDERER -->` comments with its `CACHE MAX-AGE`, `CACHE CONTEXTS` and `CACHE TAGS`.
5. Fix the cause with the patterns in `references/fixes.md`. Per-user parts go behind a `#lazy_builder` placeholder, time-based content gets a real `max-age`, and contexts get narrower, such as `user.permissions` or `user.roles` instead of `user`.

Page Cache ignores `max-age`: an anonymous page stays cached until a cache tag on it is invalidated. Time-limited anonymous content needs a tag that something invalidates on schedule, or the kill switch as a last resort.

## 5. Find what makes too many queries

1. Read the most repeated query shapes from the request probe. The same SQL run many times with different arguments is an N+1 pattern: something loads 1 entity, field or row per item in a loop.
2. Match the caller column to the code: entity loads inside a loop, `->entity` chains in Twig over a list, a count query per row, or a service that re-reads configuration on every call.
3. Fix it with the batching patterns in `references/fixes.md`, then run the probe again and compare the "other" count.
4. For a single slow query, run the explain probe from `references/probes.md` on it. A full table scan on a large table needs an index, added through the schema API in a `hook_update_N()`.

## 6. Check invalidation

- Stale content after an edit means a missing cache tag. Read `X-Drupal-Cache-Tags` for the page and check that it carries the tag of every entity and configuration object the page shows, such as `node:12` or `config:system.site`.
- Invalidate a tag by hand with `ahoy drush cache:tags node:12` and fetch the page again. If the content updates, the code that changes the data doesn't invalidate the tag; if it stays stale, the page doesn't carry the tag.
- Listings should carry a bundle list tag such as `node_list:article` instead of `node_list`, so an edit to 1 bundle doesn't clear every listing.
- Custom data cached by the code needs its own tag, invalidated through the `cache_tags.invalidator` service when the data changes.
- Avoid `ahoy drush cache:rebuild` as a fix. It hides the missing tag until the next edit.

## Redis

The `redis` service in `docker-compose.yml` runs a Redis-compatible Valkey server. `web/sites/default/includes/modules/settings.redis.php` makes it the default cache backend when the module's code is on disk and `DRUPAL_REDIS_ENABLED` is exactly `1`, which the shipped `.env` sets. The backend switch also needs the `redis` PHP extension, or `VORTEX_REDIS_EXTENSION_LOADED` set to `1`. The file also loads the module's `example.services.yml`, which moves locks, flood control and cache tag checksums to Redis, and sets `flush_redis_on_drupal_flush_cache`, so `ahoy drush cache:rebuild` flushes Redis too.

- Confirm the backend with the Redis probe in `references/probes.md`. The `render` bin should report `Drupal\redis\Cache\PhpRedis`. A database backend means the flag or the extension is missing, and every cache read is a SQL query.
- Read `evicted_keys` and the hit and miss counts from the same probe. Evictions on a full instance make warm pages render cold again, so compare `used_memory_human` with `maxmemory_human` before blaming code.
- Open `/admin/reports/redis`. It lists keys per cache bin, the render cache entries with the most variations, which points at a high-cardinality context, and the most invalidated cache tags, which points at an invalidation storm.
- Clear Redis on its own with `ahoy flush-redis`, to separate an eviction problem from a render that is slow on a clean cache.
- Change Redis settings in `settings.redis.php` only. It holds commented per-bin examples; follow its note before moving the `config`, `discovery` or `bootstrap` bins to Redis.
- [Redis](https://www.vortextemplate.com/docs/development/modules/contributed-modules#redis) lists the variables the settings file reads.

## Web server and request handling

- Nginx runs from the `uselagoon/nginx-drupal` image, built by `.docker/nginx-drupal.dockerfile`. The project's own configuration lives in `.docker/config/nginx/`: the Dockerfile copies `redirects-map.conf` to `/etc/nginx/redirects-map.conf` and each other file into `/etc/nginx/conf.d/drupal/` with a `server_prepend-` or `location_append-` prefix.
- To change caching headers for static files, add a file to `.docker/config/nginx/`, add its `COPY` line to `.docker/nginx-drupal.dockerfile`, and rebuild the container with `ahoy up -- --build nginx`. Check the new headers with the `curl -D -` command from step 2.
- A 503 with no `X-Drupal-*` headers comes from `maintenance.conf`, served while `VORTEX_WEBSERVER_MAINTENANCE_MODE` is `1`. It isn't a slow Drupal response.
- Read the web server and PHP logs with `ahoy logs -- --tail=50 nginx` and `ahoy logs -- --tail=50 php`, and Drupal's log with `ahoy drush watchdog:show --count=20`.
- In hosted environments, Automated Cron stays on; `web/sites/default/includes/modules/settings.automated_cron.php` turns it off only in `local` and `ci`. A regular spike in load can be cron, so check `ahoy drush watchdog:show --type=cron` before profiling a page.

- Requests for missing static files with the extensions listed in `web/sites/default/includes/modules/settings.fast_404.php` get a 404 before Drupal bootstraps. Missing image style derivatives still reach Drupal, which generates them on first request.

## Devel

Provisioning installs Devel in the `local`, `ci`, `dev` and `stage` environments (`scripts/provision-10-enable-dev-modules.sh`), and `settings.devel.php` keeps it out of the exported configuration. Devel has no query log, so count queries with the request probe. Use its pages to check what the running site loaded:

- `/devel/container/parameter` shows the effective `renderer.config` and `http.response.debug_cacheability_headers`, so it confirms that `services.local.yml` applied.
- `/devel/events` lists event subscribers, such as a response subscriber that sets cache headers or calls the kill switch.
- `/devel/routes` shows each route's path, controller and requirements.

## Finish

1. Run the same measurement from step 2 again, cold and warm, and compare it with the first run.
2. Restore the local settings you changed in step 1, or tell the developer which lines in `settings.local.php` you commented out.
3. Check the change against the **Caching** section of `.claude/skills/review-code/checklist.md`.
4. Delete the probe files from `.artifacts/tmp/`.
5. Report the route, the before and after numbers (query count, time to first byte, cache headers), the cause, and the fix.
