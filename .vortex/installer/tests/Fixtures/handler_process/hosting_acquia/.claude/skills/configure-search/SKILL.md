@@ -27,13 +27,13 @@
 | Modules                   | `drupal/search_api` and `drupal/search_api_solr` in `composer.json`.                                                        |
 | Module installation       | `scripts/provision-00-enable-demo-modules.sh` installs `search_api` and `search_api_solr` in `local`, `ci`, `dev` and `stage`. |
 | Indexing on provision     | `scripts/provision-30-search-index.sh` resets the tracker and indexes every enabled index in `local`, `ci`, `dev` and `stage`. Set `DRUPAL_SEARCH_INDEX_SKIP=1` to skip it. |
-| Settings overrides        | `web/sites/default/includes/modules/settings.<name>.php`, loaded for every environment by `settings.php`.                   |
+| Settings overrides        | `docroot/sites/default/includes/modules/settings.<name>.php`, loaded for every environment by `settings.php`.                   |
 
 The demo provision script is the only thing that installs the search modules, and it skips production. Before search goes live, export the configuration so `core.extension` and the search configuration reach every environment through the configuration import.
 
 ### The `sw_search` module
 
-`web/modules/custom/sw_search/` ships the starting search configuration in `config/install/`:
+`docroot/modules/custom/sw_search/` ships the starting search configuration in `config/install/`:
 
 - `search_api.server.solr.yml`: the server `solr`, connected to the `solr` service.
 - `search_api.index.content.yml`: the index `content` on that server. It indexes every node bundle, with `status` as a `boolean` field and `title` as a `solr_text_custom:ngram` field, and indexes items as soon as they are saved (`index_directly: true`).
@@ -55,7 +55,7 @@
 4. Review `git diff config/default/`. Keep the search files and the dependencies they name, and revert unrelated drift. Check that no `type:` value in `field_settings` and no `server:` value changed by accident.
 5. Reindex, as section 6 describes, whenever fields, field types or processors changed.
 
-For a value that must differ by environment, such as the Solr host on a hosting platform, don't edit the exported server. Create `web/sites/default/includes/modules/settings.solr.php` that sets `$config['search_api.server.<server_id>']['backend_config']['connector_config']['host']` and related keys from environment variables, following https://www.vortextemplate.com/docs/development/settings#create-custom-module-settings. Confirm the override with `ahoy drush config:get search_api.server.<server_id> --include-overridden`.
+For a value that must differ by environment, such as the Solr host on a hosting platform, don't edit the exported server. Create `docroot/sites/default/includes/modules/settings.solr.php` that sets `$config['search_api.server.<server_id>']['backend_config']['connector_config']['host']` and related keys from environment variables, following https://www.vortextemplate.com/docs/development/settings#create-custom-module-settings. Confirm the override with `ahoy drush config:get search_api.server.<server_id> --include-overridden`.
 
 ## 3. Servers
 
