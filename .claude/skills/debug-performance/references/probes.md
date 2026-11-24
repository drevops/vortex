# Performance probes

Copy a probe into `.artifacts/tmp/`, edit the values at its top, and run it with `ahoy drush php:script .artifacts/tmp/<name>.php`. Edit the values in the file for each run instead of passing arguments. Delete the files when the investigation is done.

## Request probe

Runs 1 request through Drupal's HTTP kernel and prints the cache backends, the time, the database queries and the response cacheability. Run it once right after `ahoy drush cache:rebuild` for the cold numbers and again for the warm numbers.

Drupal bypasses Page Cache and Dynamic Page Cache on the command line, so this measures the render work behind them. The render cache and every other cache bin still apply.

```php
<?php

/**
 * @file
 * Measures 1 internal request: time, database queries and cacheability.
 */

declare(strict_types=1);

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

// The path to measure, and the user to measure it as. 0 is anonymous.
$path = '/node/1';
$uid = 0;

foreach (['render', 'page', 'dynamic_page_cache', 'default', 'data'] as $bin) {
  try {
    printf("cache.%s: %s\n", $bin, get_class(\Drupal::cache($bin)));
  }
  catch (\Throwable $e) {
    printf("cache.%s: not available\n", $bin);
  }
}

if ($uid > 0) {
  $account = User::load($uid);
  if ($account === NULL) {
    throw new \RuntimeException(sprintf('User %d does not exist.', $uid));
  }
  \Drupal::service('account_switcher')->switchTo($account);
}

Database::startLog('perf_probe');
$start = microtime(TRUE);
$response = \Drupal::service('http_kernel')->handle(Request::create($path));
$elapsed = microtime(TRUE) - $start;
$queries = Database::getLog('perf_probe');

printf("\nPath: %s as user %d\nStatus: %d\nTime: %.1f ms\n", $path, $uid, $response->getStatusCode(), $elapsed * 1000);

$cache_count = 0;
$shapes = [];
$callers = [];
foreach ($queries as $query) {
  // Cache bin and cache tag queries leave MySQL when an in-memory cache
  // service holds the bins.
  if (preg_match('/\bcache(tags|_[a-z0-9_]+)\b/', $query['query'])) {
    $cache_count++;
    continue;
  }

  $shape = preg_replace('/\s+/', ' ', $query['query']);
  $shapes[$shape] = ($shapes[$shape] ?? 0) + 1;

  $caller = $query['caller'];
  if (is_array($caller)) {
    $caller = ($caller['class'] ?? '') . ($caller['type'] ?? '') . ($caller['function'] ?? '');
  }
  $callers[$caller] = ($callers[$caller] ?? 0) + 1;
}

printf("Queries: %d total, %d cache, %d other\n", count($queries), $cache_count, count($queries) - $cache_count);

arsort($shapes);
print "\nMost repeated query shapes:\n";
foreach (array_slice($shapes, 0, 10, TRUE) as $shape => $count) {
  printf("%5d  %s\n", $count, substr($shape, 0, 200));
}

arsort($callers);
print "\nTop callers:\n";
foreach (array_slice($callers, 0, 10, TRUE) as $caller => $count) {
  printf("%5d  %s\n", $count, $caller);
}

if ($response instanceof CacheableResponseInterface) {
  $metadata = $response->getCacheableMetadata();
  printf("\nMax-age: %d\nContexts: %s\nTags: %d\n", $metadata->getCacheMaxAge(), implode(' ', $metadata->getCacheContexts()), count($metadata->getCacheTags()));
}
```

Read the output:

- A bin that reports `Drupal\Core\Cache\NullBackend` isn't caching. Fix the local settings first, as step 1 of the skill describes.
- The "other" count is the number to compare between runs and to budget per route. A warm run should be far lower than the cold run.
- A query shape repeated 10 or more times with a matching caller is an N+1 pattern.
- `Max-age: 0`, or `user` or `session` in the contexts, makes Dynamic Page Cache report `UNCACHEABLE (poor cacheability)` for the same page over HTTP.

When the request fails with an error about the request stack or the session, measure that route over HTTP with the `curl` commands in the skill instead.

## Blocks probe

Renders the enabled blocks of the default theme 1 at a time and flags the ones that break page caching. A block that needs a route context, such as the current node, can't render outside a request and is listed as skipped.

```php
<?php

/**
 * @file
 * Prints the cacheability of each enabled block in the default theme.
 */

declare(strict_types=1);

use Drupal\Core\Cache\CacheableMetadata;

$theme = \Drupal::config('system.theme')->get('default');
$renderer = \Drupal::service('renderer');
$blocks = \Drupal::entityTypeManager()->getStorage('block')->loadByProperties(['theme' => $theme, 'status' => TRUE]);

foreach ($blocks as $block) {
  $plugin = $block->getPlugin();

  try {
    $build = $plugin->build();
    $renderer->renderInIsolation($build);
  }
  catch (\Throwable $e) {
    printf("%-40s skipped: %s\n", $block->id(), $e->getMessage());
    continue;
  }

  $metadata = CacheableMetadata::createFromRenderArray($build)->merge(CacheableMetadata::createFromObject($plugin));
  $flags = [];
  if ($metadata->getCacheMaxAge() === 0) {
    $flags[] = 'MAX-AGE 0';
  }
  if (array_intersect($metadata->getCacheContexts(), ['user', 'session'])) {
    $flags[] = 'PER-USER';
  }

  printf("%-40s %-12s max-age=%d contexts=%s\n", $block->id(), implode(',', $flags), $metadata->getCacheMaxAge(), implode(' ', $metadata->getCacheContexts()));
}
```

Fix every flagged block before looking anywhere else. A flagged block that has to stay per user goes behind a lazy builder, as `fixes.md` shows.

## Explain probe

Runs `EXPLAIN` on 1 query. Paste the SQL from the request probe's output and replace its values with placeholders.

```php
<?php

/**
 * @file
 * Prints the MySQL execution plan for 1 query.
 */

declare(strict_types=1);

$sql = 'SELECT nid FROM {node_field_data} WHERE type = :type AND status = :status ORDER BY created DESC';
$args = [':type' => 'article', ':status' => 1];

foreach (\Drupal::database()->query('EXPLAIN ' . $sql, $args)->fetchAll() as $row) {
  print_r((array) $row);
}
```

A `type` of `ALL` with a large `rows` value is a full table scan. `Using filesort` on a large result means the `ORDER BY` column has no usable index.

[//]: # (#;< SERVICE_REDIS)

## Redis probe

Confirms which backend the cache bins use and prints the Redis memory and hit statistics.

```php
<?php

/**
 * @file
 * Prints the cache backend of the main bins and the Redis statistics.
 */

declare(strict_types=1);

use Drupal\redis\ClientFactory;

foreach (['render', 'dynamic_page_cache', 'default'] as $bin) {
  printf("cache.%s: %s\n", $bin, get_class(\Drupal::cache($bin)));
}

$info = ClientFactory::getClient()->info();
foreach (['used_memory_human', 'maxmemory_human', 'maxmemory_policy', 'evicted_keys', 'keyspace_hits', 'keyspace_misses'] as $key) {
  printf("%s: %s\n", $key, $info[$key] ?? 'n/a');
}
```

- `Drupal\redis\Cache\PhpRedis` is the expected backend. `Drupal\Core\Cache\DatabaseBackend` means `settings.redis.php` didn't apply.
- A growing `evicted_keys` value means the instance is full. Pages then fall out of the cache and render cold again.
- The command line and PHP-FPM read the same settings files, but they run in different containers. When the result here disagrees with what web requests show, check the flag in the `cli` container with `ahoy cli printenv DRUPAL_REDIS_ENABLED`, and check that `/admin/reports/redis` reports a connected client for web requests.

[//]: # (#;> SERVICE_REDIS)
