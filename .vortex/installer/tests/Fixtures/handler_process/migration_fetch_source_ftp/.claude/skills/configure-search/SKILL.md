@@ -31,6 +31,8 @@
 
 The demo provision script is the only thing that installs the search modules, and it skips production. Before search goes live, export the configuration so `core.extension` and the search configuration reach every environment through the configuration import.
 
+`scripts/provision-20-migration.sh` disables the `solr` search server while migrations run and enables it again afterward. Set `DRUPAL_MIGRATION_SEARCH_DISABLE=0` to index entities as they migrate. See https://www.vortextemplate.com/docs/development/migrations#provision-script.
+
 ### The `sw_search` module
 
 `web/modules/custom/sw_search/` ships the starting search configuration in `config/install/`:
