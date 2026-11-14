---
name: create-migration
description: Use when writing, changing, running or debugging a Drupal migration in this project, such as a migration YAML definition, a process pipeline, a custom source or process plugin, the migration source database, or the Drush migrate commands and the provisioning step that runs them.
---

# Create a migration

This site imports legacy content from a second database with core's Migrate module, Migrate Plus and Migrate Tools. Turning the migration database on, choosing a fetch source, CI integration and every variable are documented at https://www.vortextemplate.com/docs/development/migrations. Read that page for setup questions instead of working them out from the files.

Run every command from the project root, 1 command per Bash call.

## How the pieces fit

| Piece                                                                | Role                                                                                                                                |
|----------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------|
| `database2` service in `docker-compose.yml`                          | Holds the legacy source database, next to the site's `database` service                                                              |
| `web/sites/default/settings.migration.php`                           | Registers the `$databases['migrate']['default']` connection from the `DATABASE2_*` variables; `settings.php` includes it             |
| `ahoy fetch-db2`                                                     | Downloads the source dump to `.data/db2.sql` from the source in `VORTEX_FETCH_DB2_SOURCE`; `--fresh` bypasses the daily cache       |
| `scripts/provision-20-migration.sh`                                  | Imports the dump into `database2`, verifies it, installs `ys_migrate`, runs the migrations and prints their status                   |
| `web/modules/custom/ys_migrate/`                                     | The migration module. Its definitions live in `migrations/`                                                                          |
| `web/modules/custom/ys_migrate/migrations/ys_migrate_categories.yml` | The demo: reads the `categories` table with the Migrate Plus `table` source plugin and creates `tags` terms                          |
| `ahoy reload-db2`                                                    | Recreates the `database2` container from its image and discards its data; the next provisioning imports the dump again               |
| `ahoy db2`                                                           | Opens the source database in Sequel Ace on macOS                                                                                     |

Source plugins read the `migrate` connection: set `key: migrate` in the source, as the demo does.

## 1. Inspect the source data

1. Make sure the dump exists: `ahoy fetch-db2`. The source must be configured first; see the documentation page above.
2. List the tables and read the structure of the ones you need. `.ahoy.yml` passes arguments through a shell, so escape the quotes around a query that contains spaces:

   ```shell
   ahoy drush sql:query --database=migrate \"SHOW TABLES\"
   ahoy drush sql:query --database=migrate \"DESCRIBE categories\"
   ahoy drush sql:query --database=migrate \"SELECT * FROM categories LIMIT 5\"
   ```

3. Write down, for each table: the column or columns that identify a row, the columns that reference other tables, multi-value data stored as lists or join tables, status or deletion flags, and the format of dates and HTML.
4. Confirm that the destination bundle and every destination field exist on the site. A migration doesn't create fields.

## 2. Write the migration definition

Create `web/modules/custom/ys_migrate/migrations/<id>.yml`, with the file name matching the `id`. Drupal discovers definitions only in enabled modules, and the provision script installs `ys_migrate` alone, so keep definitions in that module unless you also install your own module there (see section 4).

Prefix each ID with `ys_migrate_` and tag it `ys_migrate`, so the whole set can be listed and run by tag. For example, a migration of legacy articles into `page` nodes that references the demo categories:

```yaml
id: ys_migrate_articles
label: 'Migrate legacy articles to pages'
migration_tags:
  - ys_migrate

source:
  plugin: table
  key: migrate
  table_name: articles
  id_fields:
    article_id:
      type: integer
  fields:
    article_id: article_id
    headline: headline
    body_html: body_html
    category_id: category_id
    published_on: published_on

process:
  type:
    plugin: default_value
    default_value: page
  title: headline
  body/value: body_html
  body/format:
    plugin: default_value
    default_value: plain_text
  created:
    plugin: format_date
    source: published_on
    from_format: 'Y-m-d H:i:s'
    to_format: U
  field_category:
    plugin: migration_lookup
    migration: ys_migrate_categories
    source: category_id
    no_stub: true

destination:
  plugin: 'entity:node'

migration_dependencies:
  required:
    - ys_migrate_categories
```

The table, its columns and `field_category` are placeholders for your own. Rules that apply to every definition:

- Each `process` key is a destination property. A `/` reaches into a field property, such as `body/value`.
- A value of a plain string copies a source property unchanged. A list of plugins runs as a pipeline, each plugin receiving the previous result.
- A key prefixed with `@` reads a destination property processed earlier in the same row, such as `'@title'`.
- Every ID in `migration_dependencies` must match an existing migration ID. List required dependencies before the migration in the provision script too (section 4).
- Definitions are cached. After creating or changing a YAML file, run `ahoy drush cr`.

To choose the process plugins for each field, read [references/process-plugins.md](references/process-plugins.md). It covers mapping values, looking up migrated or existing entities, generating missing terms, multi-value data and skipping rows or fields.

When the `table` source can't express the query, or a value needs logic no plugin provides, write a custom plugin: [references/custom-plugins.md](references/custom-plugins.md).

## 3. Run and iterate

Run migrations with Drush through `ahoy drush`. Start small, read the messages, then widen:

```shell
ahoy drush cr
ahoy drush migrate:status --tag=ys_migrate
ahoy drush migrate:fields-source ys_migrate_articles
ahoy drush migrate:import ys_migrate_articles --limit=10
ahoy drush migrate:messages ys_migrate_articles
ahoy drush migrate:import ys_migrate_articles --idlist=42
ahoy drush migrate:import ys_migrate_articles --update
ahoy drush migrate:import ys_migrate_articles --execute-dependencies
ahoy drush migrate:rollback ys_migrate_articles
ahoy drush migrate:reset-status ys_migrate_articles
ahoy drush migrate:stop ys_migrate_articles
```

- `--update` re-processes rows that were already imported. Without it, a change to the process section affects new rows only. Roll back and import again when the change affects which rows exist.
- `--idlist` takes source IDs. Join the columns of a multi-column ID with `:`.
- `migrate:reset-status` clears a migration left in the `Importing` state by an interrupted run.
- Check the result on the site, not only the counts: open migrated content through the link from `ahoy login`, or write a script under `.artifacts/tmp/` and run it with `ahoy drush php:script .artifacts/tmp/<name>.php`. Never use `drush php:eval`.

## 4. Run it during provisioning

`scripts/provision-20-migration.sh` runs as part of `ahoy provision` and `ahoy build`, after the demo modules script and before search indexing. It skips every migration in the `prod` environment. See https://www.vortextemplate.com/docs/development/provision#run-custom-scripts for how custom provision scripts run.

1. Add a `run_migration` line for each migration in the `Add your migrations below.` section, below `run_migration ys_migrate_categories`, with dependencies first:

   ```bash
   run_migration ys_migrate_articles
   ```

   The helper resets the migration's status, passes the limit, feedback and update settings and any extra arguments to `drush migrate:import`, and stops the provisioning with the migration's messages when the import fails.

2. When the migrations live in another module, add a `drush pm:install <module>` line to the `Enabling migration modules` step.
3. Set `DRUPAL_MIGRATION_SOURCE_DB_PROBE_TABLE` to a table that exists in your source database. The default, `categories`, belongs to the demo source, and the script stops the provisioning when the probe table is missing.
4. Review the variables in `.env` or `.env.local`. The ones you reach for most: `DRUPAL_MIGRATION_IMPORT_LIMIT` (default `50` rows per migration; `all` for no limit), `DRUPAL_MIGRATION_UPDATE=1` to re-process imported rows, `DRUPAL_MIGRATION_ROLLBACK_SKIP=0` to roll back before importing, and `DRUPAL_MIGRATION_SKIP=1` to skip migrations. The full table is on the documentation page.
5. `ahoy provision` replaces the local database and asks for confirmation. Ask the developer before running it. With their approval, run `AHOY_CONFIRM_RESPONSE=y ahoy provision`.
6. Confirm that the output shows `Verified migration source database.`, a `Migrated: <id>.` line for each migration and the final `migrate:status` table.

On sites with the Solr service, the script disables the Solr search server while the migrations run and enables it again afterward, and `scripts/provision-30-search-index.sh` rebuilds the index right after. Set `DRUPAL_MIGRATION_SEARCH_DISABLE=0` to index entities as they arrive.

## 5. Troubleshoot

| Symptom                                                                       | Cause and fix                                                                                                                                     |
|-------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------|
| `Migration source database file not found. Please run 'ahoy fetch-db2'.`      | The dump is missing from `.data/`. Run `ahoy fetch-db2`                                                                                            |
| `Migration source database is corrupted.`                                     | The probe table is missing. Set `DRUPAL_MIGRATION_SOURCE_DB_PROBE_TABLE` to a real table, or run `ahoy reload-db2` and provision again            |
| The migration is missing from `migrate:status`                                | The module isn't enabled, the file name doesn't match the `id`, or the cache is stale. Run `ahoy drush cr`                                         |
| The status stays `Importing`                                                  | An earlier run was interrupted. Run `ahoy drush migrate:reset-status <id>`                                                                         |
| Rows are counted as failed or ignored                                         | Read `ahoy drush migrate:messages <id>`. A skip plugin with a `message` records why it skipped the row                                             |
| A change to the process section has no effect                                 | Run `ahoy drush cr`, then import with `--update`                                                                                                   |
| A reference field is empty                                                    | The referenced migration hasn't run, or the lookup names the wrong migration or source property. Import the dependency first                       |
| Only some rows arrive after provisioning                                      | `DRUPAL_MIGRATION_IMPORT_LIMIT` defaults to `50`. Set it to `all`                                                                                  |
| Connection errors on the `migrate` database                                   | The `database2` container isn't running or the `DATABASE2_*` values are wrong. Run `ahoy up` and check `ahoy info`                                 |

## 6. Test custom code

A custom process plugin is a plain class: cover its `transform()` method with a unit test that passes a value and mocked `MigrateExecutableInterface` and `Row` objects, with no database. Follow the project's conventions in `docs/testing.md`. The YAML mapping itself is proven by running the migration and checking the migrated content, as section 3 describes.
