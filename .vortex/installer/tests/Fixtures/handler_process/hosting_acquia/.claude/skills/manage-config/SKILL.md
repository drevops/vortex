@@ -7,7 +7,7 @@
 
 Move configuration between the database and the files in `config/default` without changing anything the developer hasn't seen. Every import and every export starts with a preview.
 
-Run every command from the project root, 1 command per Bash call. `.env` sets the sync directory through `DRUPAL_CONFIG_PATH`, which `web/sites/default/settings.php` reads into `$settings['config_sync_directory']`.
+Run every command from the project root, 1 command per Bash call. `.env` sets the sync directory through `DRUPAL_CONFIG_PATH`, which `docroot/sites/default/settings.php` reads into `$settings['config_sync_directory']`.
 
 ## Rules
 
@@ -17,7 +17,7 @@
 - After any change made in the admin UI, export the configuration. This is an `AGENTS.md` rule.
 - Run imports, exports, `config:set` and `config:delete` against the local site only, never against a remote site alias such as `@<alias>`.
 - Never use `drush php:eval`. Write PHP to a file under `.artifacts/tmp/manage-config/` and run it with `ahoy drush php:script /app/.artifacts/tmp/manage-config/<file>.php`. The project is mounted at `/app` in the `cli` container, so container paths start with `/app`.
-- Values set in the per-module files `web/sites/default/includes/modules/settings.<module>.php` are overrides. They never reach the exported files. To change such a value, edit that file instead.
+- Values set in the per-module files `docroot/sites/default/includes/modules/settings.<module>.php` are overrides. They never reach the exported files. To change such a value, edit that file instead.
 
 ## Check the state
 
@@ -107,7 +107,7 @@
 
 ## Config Split
 
-`web/sites/default/includes/modules/settings.config_split.php` turns on 1 split per environment type, through a `$config['config_split.config_split.<name>']['status'] = TRUE` override:
+`docroot/sites/default/includes/modules/settings.config_split.php` turns on 1 split per environment type, through a `$config['config_split.config_split.<name>']['status'] = TRUE` override:
 
 | Environment type | Active split | Split directory |
 |------------------|--------------|-----------------|
