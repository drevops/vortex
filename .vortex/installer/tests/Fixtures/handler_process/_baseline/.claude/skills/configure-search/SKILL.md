---
name: configure-search
description: Use when asked to configure, change or debug site search with Search API and Solr - add or change a search server, index, indexed field or processor, build or fix a view on a search index, reindex or clear an index, update the Solr configuration set, or find out why search results are missing, stale or ranked badly.
---

# Configure search

Configure Search API with the Solr backend on this site, and debug it when results go wrong. Search configuration is Drupal configuration, so every change ends as exported YAML in `config/default/`.

Run every command from the project root. Every command goes through `ahoy`, 1 command per Bash call. Never use `drush php:eval`. For ad-hoc PHP, write a file under `.artifacts/tmp/` and run it with `ahoy drush php:script .artifacts/tmp/<file>.php`.

## 1. Learn the setup

The template documents its search setup. Read these pages before changing anything, and link to them instead of restating them:

- The `solr` Docker service: https://www.vortextemplate.com/docs/development/environment/docker#services
- The search indexing provision script: https://www.vortextemplate.com/docs/development/provision#search-indexing
- The Search API and Search API Solr modules: https://www.vortextemplate.com/docs/development/modules/contributed-modules#search-api-solr

The facts below come from the repository. Check them against the current files, because the project may have changed them.

| Part                      | Where                                                                                                                       |
|---------------------------|-----------------------------------------------------------------------------------------------------------------------------|
| Solr service              | `solr` in `docker-compose.yml`, built from `.docker/solr.dockerfile`. Solr data lives in the `solr` volume.                 |
| Solr configuration set    | `.docker/config/solr/config-set/`, copied into the image. The image pre-creates 1 core named `drupal`.                      |
| Connection from Drupal    | Host `solr`, port `8983`, path `/`, core `drupal`, scheme `http`.                                                           |
| Modules                   | `drupal/search_api` and `drupal/search_api_solr` in `composer.json`.                                                        |
| Module installation       | `scripts/provision-00-enable-demo-modules.sh` installs `search_api` and `search_api_solr` in `local`, `ci`, `dev` and `stage`. |
| Indexing on provision     | `scripts/provision-30-search-index.sh` resets the tracker and indexes every enabled index in `local`, `ci`, `dev` and `stage`. Set `DRUPAL_SEARCH_INDEX_SKIP=1` to skip it. |
| Settings overrides        | `web/sites/default/includes/modules/settings.<name>.php`, loaded for every environment by `settings.php`.                   |

The demo provision script is the only thing that installs the search modules, and it skips production. Before search goes live, export the configuration so `core.extension` and the search configuration reach every environment through the configuration import.

### The `sw_search` module

`web/modules/custom/sw_search/` ships the starting search configuration in `config/install/`:

- `search_api.server.solr.yml`: the server `solr`, connected to the `solr` service.
- `search_api.index.content.yml`: the index `content` on that server. It indexes every node bundle, with `status` as a `boolean` field and `title` as a `solr_text_custom:ngram` field, and indexes items as soon as they are saved (`index_directly: true`).
- `views.view.search.yml`: the view `search`, a page at `/search` on the `content` index. It has an exposed fulltext filter with the identifier `search_api_fulltext`, a fixed filter on `status`, and renders each result in the `teaser` view mode.

`sw_search_install()` in `sw_search.install` uninstalls the core `search` module. The deploy hook in `sw_search.deploy.php` attaches an editorial workflow to the `page` content type. See https://www.vortextemplate.com/docs/development/modules/module-scaffold#other-shipped-modules.

Drupal reads `config/install/` only when the module is installed. After that, the active configuration and its export in `config/default/` are the source of truth. Edit the exported files, never `config/install/`, unless the change must reach fresh installs of the module as well.

In this project the server ID is `solr` and the index ID is `content`. Use them wherever this skill says `<server_id>` and `<index_id>`.

`tests/behat/features/search.feature` indexes 3 pages and searches them through `/search`. Run it after a change with `ahoy test-bdd -- --tags=@search`.

## 2. Change configuration the safe way

1. Make the change in the administration UI at `/admin/config/search/search-api`, or edit the exported YAML in `config/default/` and run `ahoy drush cim`. Get a login link with `ahoy login`.
2. Read the result with `ahoy drush config:get search_api.index.<index_id>` or `ahoy drush config:get search_api.server.<server_id>`.
3. Export with `ahoy drush cex`.
4. Review `git diff config/default/`. Keep the search files and the dependencies they name, and revert unrelated drift. Check that no `type:` value in `field_settings` and no `server:` value changed by accident.
5. Reindex, as section 6 describes, whenever fields, field types or processors changed.

For a value that must differ by environment, such as the Solr host on a hosting platform, don't edit the exported server. Create `web/sites/default/includes/modules/settings.solr.php` that sets `$config['search_api.server.<server_id>']['backend_config']['connector_config']['host']` and related keys from environment variables, following https://www.vortextemplate.com/docs/development/settings#create-custom-module-settings. Confirm the override with `ahoy drush config:get search_api.server.<server_id> --include-overridden`.

## 3. Servers

- List servers and their status with `ahoy drush search-api:server-list`.
- The server page in the UI reports whether Drupal reaches Solr and whether the core's configuration set matches what the module expects.
- Point every server at the connection values in section 1 for the local stack. Keep `skip_schema_check` off, so a mismatched configuration set shows up on the server page.
- Move an index to another server with `ahoy drush search-api:set-index-server <index_id> <server_id>`, then reindex.

### Update the Solr configuration set

Regenerate the configuration set after a `drupal/search_api_solr` update, or when the server page reports an outdated one:

1. Run `ahoy drush search-api-solr:get-server-config <server_id> /app/.artifacts/tmp/solr-config.zip`. The CLI container mounts the project at `/app`.
2. Extract the archive over `.docker/config/solr/config-set/`.
3. Restore `solr.luceneMatchVersion` in `solrcore.properties` to the value it had. `.docker/solr.dockerfile` explains why it must match the Lucene version in the image.
4. Review `git diff .docker/config/solr/`.
5. Rebuild and restart the service with `ahoy up --build --force-recreate solr`.
6. Open the Solr admin UI on the port that `ahoy info` prints and confirm the `drupal` core loaded the new files. Then reindex.

The image creates the core only when the `solr` volume has none, so an existing core can keep its earlier configuration. When the admin UI still shows the old files, ask the developer before removing the volume: `ahoy down` removes every volume, including the database.

## 4. Indexes, fields and processors

- **Datasources**: choose the entity types and bundles. Index only what a search page shows, because every extra bundle slows indexing and adds noise.
- **Fields**: add only fields that a search, filter, sort or facet reads. Use `boolean`, `integer`, `date` or `string` for values that filters and sorts read, and a fulltext type for searched text. `solr_text_custom:*` types, such as the ngram type, add partial-word matching through the Solr field types in the configuration set.
- **Boost**: a fulltext field takes a `boost` that weighs matches in that field more, such as the title over the body.
- **Processors**: Solr analyzes fulltext fields itself, so leave the Search API text processors such as tokenizer, ignore case and stopwords off and tune the Solr field types instead. Add `entity_status` to keep unpublished items out of the index, or `content_access` to apply node access, when a view doesn't filter them already.
- **Options**: `index_directly` indexes items as soon as they are saved. `cron_limit` sets how many items each cron run indexes.

Ranking, boosts and custom processors are covered in [references/relevance.md](references/relevance.md).

## 5. Views on an index

- Create the view with **Show: Index <name>** in the wizard. Its base table is `search_api_index_<index_id>` and its query plugin is `search_api_query`.
- Add the **Fulltext search** filter (`search_api_fulltext`) and expose it for keyword search. Set **Searched fields** when only some fulltext fields should match.
- Render results as entities with the `search_api` row plugin and a view mode, or as fields that the index stores.
- Use a Search API cache plugin, such as `search_api_tag`, or `search_api_none` while debugging.
- Export the view with `ahoy drush cex` like any other configuration.

## 6. Index, reindex and clear

Every command takes an optional index ID. Without it, the command runs on every enabled index.

| Command                                                  | Effect                                                                     |
|----------------------------------------------------------|----------------------------------------------------------------------------|
| `ahoy drush search-api:list`                             | Lists indexes, their server and their status.                              |
| `ahoy drush search-api:status <index_id>`                | Shows indexed and total items.                                             |
| `ahoy drush search-api:index <index_id>`                 | Indexes the items waiting in the tracker. Add `--limit=<n>` or `--batch-size=<n>`. |
| `ahoy drush search-api:reset-tracker <index_id>`         | Marks every item for reindexing. Indexed documents stay until replaced.    |
| `ahoy drush search-api:rebuild-tracker <index_id>`       | Rebuilds the list of tracked items from the datasources.                   |
| `ahoy drush search-api:clear <index_id>`                 | Deletes the index's documents from the server and marks every item for reindexing. |
| `ahoy drush search-api:server-clear <server_id>`         | Clears every index on the server.                                          |
| `ahoy drush search-api:search <index_id> <keyword>`      | Runs a search without Views and prints the results.                        |
| `ahoy drush search-api:enable <index_id>` and `search-api:disable` | Enable or disable 1 index.                                       |
| `ahoy drush search-api:server-enable <server_id>` and `search-api:server-disable` | Enable or disable a server.                       |

Pick the sequence for the change:

- Field boost, processor setting or rendered output changed: `search-api:reset-tracker`, then `search-api:index`.
- Field removed, field type changed or stale documents found: `search-api:clear`, then `search-api:index`.
- Datasource or bundle selection changed: `search-api:rebuild-tracker`, then `search-api:index`.

Indexing a large site takes a while, so run `search-api:index` with `run_in_background`. `ahoy drush` passes arguments through a shell string, so escape the quotes around a multi-word keyword as `\"two words\"`, as the `cli` command in `.ahoy.yml` notes.

## 7. Debug missing or wrong results

Work down the list and stop at the first step that fails.

1. `ahoy drush search-api:server-list`: the server is enabled. When it isn't, read `ahoy logs solr` and check that the stack is up with `ahoy info`.
2. `ahoy drush search-api:list`: the index is enabled and assigned to that server.
3. `ahoy drush search-api:status <index_id>`: items are tracked and indexed. 0 tracked items means the datasource or bundle selection is wrong; run `search-api:rebuild-tracker`. Tracked but unindexed items need `search-api:index`.
4. After a database import, the tracker can claim items are indexed while the local Solr core is empty. Run `search-api:reset-tracker` and `search-api:index`, which is what the provision script does.
5. `ahoy drush search-api:search <index_id> <keyword>`: Search API finds the content. When this works but the page doesn't, the problem is in the view: its filters, the searched fields, the exposed filter identifier, access or caching.
6. When a field holds unexpected values, read its configuration with `ahoy drush config:get search_api.index.<index_id> field_settings` and query the core in the Solr admin UI on the port from `ahoy info`.
7. For deeper checks, run a query in PHP. Write the file to `.artifacts/tmp/search-debug.php` and run `ahoy drush php:script .artifacts/tmp/search-debug.php`:

```php
<?php

use Drupal\search_api\Entity\Index;

$index = Index::load('INDEX_ID');
$query = $index->query();
$query->keys('KEYWORD');
$query->range(0, 10);
$results = $query->execute();

print 'Results: ' . $results->getResultCount() . PHP_EOL;
foreach ($results->getResultItems() as $item_id => $item) {
  print $item_id . ' ' . $item->getScore() . PHP_EOL;
}
```

Delete the file when done.

## Report

Tell the developer what changed in the server, index, fields, processors or views, which files changed under `config/default/` or `.docker/config/solr/`, which reindex commands ran, and the result counts before and after.
