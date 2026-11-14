@@ -31,22 +31,6 @@
 
 The demo provision script is the only thing that installs the search modules, and it skips production. Before search goes live, export the configuration so `core.extension` and the search configuration reach every environment through the configuration import.
 
-### The `sw_search` module
-
-`web/modules/custom/sw_search/` ships the starting search configuration in `config/install/`:
-
-- `search_api.server.solr.yml`: the server `solr`, connected to the `solr` service.
-- `search_api.index.content.yml`: the index `content` on that server. It indexes every node bundle, with `status` as a `boolean` field and `title` as a `solr_text_custom:ngram` field, and indexes items as soon as they are saved (`index_directly: true`).
-- `views.view.search.yml`: the view `search`, a page at `/search` on the `content` index. It has an exposed fulltext filter with the identifier `search_api_fulltext`, a fixed filter on `status`, and renders each result in the `teaser` view mode.
-
-`sw_search_install()` in `sw_search.install` uninstalls the core `search` module. The deploy hook in `sw_search.deploy.php` attaches an editorial workflow to the `page` content type. See https://www.vortextemplate.com/docs/development/modules/module-scaffold#other-shipped-modules.
-
-Drupal reads `config/install/` only when the module is installed. After that, the active configuration and its export in `config/default/` are the source of truth. Edit the exported files, never `config/install/`, unless the change must reach fresh installs of the module as well.
-
-In this project the server ID is `solr` and the index ID is `content`. Use them wherever this skill says `<server_id>` and `<index_id>`.
-
-`tests/behat/features/search.feature` indexes 3 pages and searches them through `/search`. Run it after a change with `ahoy test-bdd -- --tags=@search`.
-
 ## 2. Change configuration the safe way
 
 1. Make the change in the administration UI at `/admin/config/search/search-api`, or edit the exported YAML in `config/default/` and run `ahoy drush cim`. Get a login link with `ahoy login`.
