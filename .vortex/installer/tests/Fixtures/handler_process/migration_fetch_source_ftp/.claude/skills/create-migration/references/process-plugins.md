# Process plugins

Pick the plugin by the job the field needs. Plugins marked "core" ship with Drupal's Migrate module, and the others come from Migrate Plus, which `ys_migrate` already depends on. Every example below is a fragment of a `process` section, and the source property names are placeholders.

| Job                                                       | Plugin                                   | Provider     |
|-----------------------------------------------------------|------------------------------------------|--------------|
| Set a constant value                                      | `default_value`                          | core         |
| Translate legacy codes into new values                    | `static_map`                             | core         |
| Reference an entity this site migrated                    | `migration_lookup`                       | core         |
| Reference an entity that already exists on the site       | `entity_lookup`                          | Migrate Plus |
| Reference an entity, creating it when it doesn't exist    | `entity_generate`                        | Migrate Plus |
| Build a list of structured values, such as links          | `sub_process`                            | core         |
| Skip a field or a row when the value is empty             | `skip_on_empty`                          | core         |
| Skip a field or a row when the value matches              | `skip_on_value`                          | Migrate Plus |
| Join or split strings                                     | `concat`, `explode`                      | core         |
| Run a PHP function on the value                           | `callback`                               | core         |
| Convert a date                                            | `format_date`                            | core         |
| Take the first value that isn't empty                     | `null_coalesce`                          | core         |
| Replace text                                              | `str_replace`                            | Migrate Plus |

## Map legacy values

```yaml
status:
  plugin: static_map
  source: state
  map:
    live: 1
    draft: 0
  default_value: 0
```

Without `default_value` or `bypass: true`, a value missing from `map` skips the row.

## Reference migrated entities

```yaml
field_category:
  plugin: migration_lookup
  migration: ys_migrate_categories
  source: category_id
  no_stub: true
```

`migration_lookup` finds the destination ID that a migration created for a source ID. Without `no_stub: true`, a missing match creates an empty placeholder entity, which is rarely what an editor wants. List the referenced migration in `migration_dependencies` and run it first.

## Reference existing entities

```yaml
uid:
  plugin: entity_lookup
  source: author_email
  entity_type: user
  value_key: mail
  ignore_case: true
  access_check: false
```

`entity_lookup` searches the site for an entity whose `value_key` field matches the source value. Set `access_check: false` when the migration runs from Drush, because the query otherwise runs with the access of the Drush user.

## Generate missing terms

```yaml
field_tags:
  - plugin: explode
    source: keywords
    delimiter: ','
  - plugin: callback
    callable: trim
  - plugin: entity_generate
    entity_type: taxonomy_term
    bundle_key: vid
    bundle: tags
    value_key: name
```

`entity_generate` looks the value up like `entity_lookup` and creates the entity when nothing matches. Its `default_values` and `values` keys fill other fields on the created entity. Plugins that handle 1 value at a time, such as `callback`, run once per item of a list.

## Build structured multi-value fields

```yaml
field_links:
  plugin: sub_process
  source: links
  process:
    uri: url
    title: label
```

`sub_process` runs its own `process` section on every item of a source list, and each item must be an array. A SQL source returns flat rows, so build the list in the source plugin's `prepareRow()` (see `custom-plugins.md`).

## Skip fields and rows

```yaml
field_summary:
  plugin: skip_on_empty
  method: process
  source: summary

status:
  - plugin: skip_on_value
    source: state
    method: row
    value: deleted
    message: 'Deleted on the legacy site.'
  - plugin: static_map
    map:
      live: 1
      draft: 0
```

`method: process` leaves the destination property unset and continues with the row. `method: row` drops the whole row, and `message` records the reason in `migrate:messages`. `skip_on_value` also accepts a list in `value`, and `not_equals: true` skips when the value doesn't match.

## Reuse earlier values

```yaml
source:
  constants:
    prefix: '/news/'

process:
  _slug:
    plugin: machine_name
    source: headline
  path/alias:
    plugin: concat
    source:
      - constants/prefix
      - '@_slug'
```

Constants declared under `source.constants` read like source properties. A process key that names no destination field, such as `_slug`, holds an intermediate value, and `@` reads it in a later key. The leading `_` is a naming convention that keeps such keys apart from real fields.

## Convert dates

```yaml
created:
  plugin: format_date
  source: published_on
  from_format: 'Y-m-d H:i:s'
  to_format: U
  from_timezone: Australia/Sydney
```

Set `from_timezone` when the legacy site stored local times, or every timestamp shifts by the site's offset.
