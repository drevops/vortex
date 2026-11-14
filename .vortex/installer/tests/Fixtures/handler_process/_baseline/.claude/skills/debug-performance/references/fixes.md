# Performance fixes

Patterns for the causes the skill finds. Each example uses the `sw_base` custom module; replace the names with the module that owns the code. Measure before and after each fix with the request probe in [probes.md](probes.md).

## How cacheability bubbles

Every render array carries 3 kinds of cacheability in `#cache`:

- **Tags** name the data the output depends on, such as `node:12` or `config:system.site`. Invalidating a tag clears every cached item that carries it.
- **Contexts** name the request values the output varies by, such as `user.permissions` or `url.query_args:page`. Each combination of values gets its own cache entry.
- **Max-age** is how many seconds the output stays valid. `-1` (`Cache::PERMANENT`) means until a tag invalidates it, and `0` means never cache.

When Drupal renders a tree, each child's cacheability merges into its parents, up to the response: tags and contexts add up, and the lowest max-age wins. 1 element with `max-age` 0 or a `user` context deep in the tree makes the whole page uncacheable for Dynamic Page Cache, unless that element becomes a placeholder.

The `required_cache_contexts` in `web/sites/default/services.yml` add `languages:language_interface`, `theme` and `user.permissions` to every render array, so never add those by hand.

## Declare cacheability with the data

```php
// Output built from an entity and a configuration object.
$build = [
  '#theme' => 'sw_base_summary',
  '#title' => $node->label(),
  '#site_name' => $site_config->get('name'),
];
CacheableMetadata::createFromRenderArray($build)
  ->addCacheableDependency($node)
  ->addCacheableDependency($site_config)
  ->applyTo($build);
```

```php
// A block plugin: declare cacheability on the plugin, not only in build().
public function getCacheTags(): array {
  return Cache::mergeTags(parent::getCacheTags(), ['node_list:article']);
}

public function getCacheContexts(): array {
  return Cache::mergeContexts(parent::getCacheContexts(), ['url.query_args:page']);
}
```

Use `$this->renderer->addCacheableDependency($build, $object)` when the code adds a dependency to an existing render array.

## Narrow the contexts

| Instead of | Use | When the output varies by |
|---|---|---|
| `user` | `user.permissions` | What the user may do |
| `user` | `user.roles` or `user.roles:editor` | Role membership |
| `user` | `user.roles:authenticated` | Logged in or not |
| `session` | `session.exists` | Whether a session exists |
| `url` | `url.path` or `route` | The page, not its query string |
| `url.query_args` | `url.query_args:page` | 1 query parameter |

Keep `user` only for output that really differs per user, and put that output behind a lazy builder.

## Put per-user output behind a placeholder

A `#lazy_builder` element renders after the rest of the page comes out of the cache, so its cacheability doesn't bubble to the page. Its arguments must be scalars, and its callback must be trusted.

```php
// Where the page is built.
$build['greeting'] = [
  '#lazy_builder' => ['sw_base.greeting_builder:build', [(int) $node->id()]],
  '#create_placeholder' => TRUE,
];
```

```yaml
# sw_base.services.yml
services:
  sw_base.greeting_builder:
    class: Drupal\sw_base\GreetingBuilder
    arguments: ['@current_user', '@entity_type.manager']
```

```php
namespace Drupal\sw_base;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class GreetingBuilder implements TrustedCallbackInterface {

  use StringTranslationTrait;

  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function trustedCallbacks(): array {
    return ['build'];
  }

  public function build(int $nid): array {
    return [
      '#markup' => $this->t('Welcome back, @name.', ['@name' => $this->currentUser->getDisplayName()]),
      '#cache' => ['contexts' => ['user'], 'tags' => ['node:' . $nid]],
    ];
  }

}
```

## Replace max-age 0

- Content that changes on a schedule gets a real `max-age`, such as `3600`, instead of `0`.
- Page Cache ignores `max-age`, so an anonymous page keeps time-limited content until a tag on it is invalidated. Give that content a custom tag and invalidate it from cron when it expires.
- Use the `page_cache_kill_switch` service only for a response that must never reach Page Cache, such as one that shows data from the visitor's own request, and call `trigger()` only on the requests that need it.
- Show messages to anonymous users only where needed: a message starts a session, and Page Cache skips requests with a session.

## Invalidate by tag

```php
// After the code changes data that cached output depends on.
$this->cacheTagsInvalidator->invalidateTags(['sw_base:partner_feed']);
```

- Entities and configuration objects invalidate their own tags when saved, so output that declares them as dependencies needs no extra code.
- A listing of 1 bundle carries the bundle list tag, such as `node_list:article`. `node_list` clears on every node save of any bundle.
- Invalidate a custom tag in the code that writes the data, such as a queue worker, a form submit handler or an entity hook, never on a timer that runs whether the data changed or not.

## Cache expensive results

```php
// A service that receives '@cache.default'.
$cid = 'sw_base:partner_feed:' . $langcode;
if ($cached = $this->cache->get($cid)) {
  return $cached->data;
}

$items = $this->fetchItems($langcode);
$this->cache->set($cid, $items, Cache::PERMANENT, ['sw_base:partner_feed']);

return $items;
```

For a value reused many times within 1 request, keep it in a protected array property on the service. Services are created once per request.

## Batch entity loads

```php
// N+1: 1 load per item.
foreach ($nids as $nid) {
  $titles[] = $storage->load($nid)->label();
}

// 1 query for all items.
foreach ($storage->loadMultiple($nids) as $node) {
  $titles[] = $node->label();
}

// Referenced entities of a field, loaded together.
foreach ($node->get('field_tags')->referencedEntities() as $term) {
  $names[] = $term->label();
}
```

```php
// N+1: 1 count query per category.
foreach ($categories as $tid) {
  $counts[$tid] = $storage->getQuery()->accessCheck(TRUE)->condition('field_category', $tid)->count()->execute();
}

// 1 aggregate query for all categories.
$rows = $storage->getAggregateQuery()
  ->accessCheck(TRUE)
  ->condition('field_category', $categories, 'IN')
  ->groupBy('field_category')
  ->aggregate('nid', 'COUNT')
  ->execute();
```

- In Twig, a chain such as `node.field_author.entity.field_photo.entity` loads entities inside the template, once per row of a list. Load the entities in a preprocess function with `loadMultiple()` for the whole list instead.
- Give every listing query a `range()`. An unbounded query grows with the content.

## Keep outbound requests out of the render path

- Fetch remote data in cron or a queue worker, store it in a cache bin or in entities, and render from there.
- When a request in the render path can't be avoided, pass a `timeout` option to the `http_client` service and cache the result, so 1 slow remote service doesn't block every cold render.

## Add a database index

```php
/**
 * Adds an index on the 'created' column of the 'sw_base_log' table.
 */
function sw_base_update_11001(): void {
  $schema = \Drupal::database()->schema();
  if ($schema->indexExists('sw_base_log', 'sw_base_log__created')) {
    return;
  }

  $schema->addIndex('sw_base_log', 'sw_base_log__created', ['created'], [
    'fields' => ['created' => ['type' => 'int', 'not null' => TRUE]],
  ]);
}
```

Add the same index to the table definition in `hook_schema()`, so new installs get it. Run the explain probe again to confirm the plan uses the index.
