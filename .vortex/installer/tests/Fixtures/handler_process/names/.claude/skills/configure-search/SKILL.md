@@ -31,15 +31,15 @@
 
 The demo provision script is the only thing that installs the search modules, and it skips production. Before search goes live, export the configuration so `core.extension` and the search configuration reach every environment through the configuration import.
 
-### The `sw_search` module
+### The `the_force_search` module
 
-`web/modules/custom/sw_search/` ships the starting search configuration in `config/install/`:
+`web/modules/custom/the_force_search/` ships the starting search configuration in `config/install/`:
 
 - `search_api.server.solr.yml`: the server `solr`, connected to the `solr` service.
 - `search_api.index.content.yml`: the index `content` on that server. It indexes every node bundle, with `status` as a `boolean` field and `title` as a `solr_text_custom:ngram` field, and indexes items as soon as they are saved (`index_directly: true`).
 - `views.view.search.yml`: the view `search`, a page at `/search` on the `content` index. It has an exposed fulltext filter with the identifier `search_api_fulltext`, a fixed filter on `status`, and renders each result in the `teaser` view mode.
 
-`sw_search_install()` in `sw_search.install` uninstalls the core `search` module. The deploy hook in `sw_search.deploy.php` attaches an editorial workflow to the `page` content type. See https://www.vortextemplate.com/docs/development/modules/module-scaffold#other-shipped-modules.
+`the_force_search_install()` in `the_force_search.install` uninstalls the core `search` module. The deploy hook in `the_force_search.deploy.php` attaches an editorial workflow to the `page` content type. See https://www.vortextemplate.com/docs/development/modules/module-scaffold#other-shipped-modules.
 
 Drupal reads `config/install/` only when the module is installed. After that, the active configuration and its export in `config/default/` are the source of truth. Edit the exported files, never `config/install/`, unless the change must reach fresh installs of the module as well.
 
