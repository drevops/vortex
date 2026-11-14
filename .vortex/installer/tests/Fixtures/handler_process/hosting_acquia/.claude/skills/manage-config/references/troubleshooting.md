@@ -6,12 +6,12 @@
 
 A full export writes only what the export storage holds, and deletes files for everything else.
 
-- An excluded module owns the item. Modules in `$settings['config_exclude_modules']` stay out of the export, and so does their configuration. Check: `grep -rn config_exclude_modules web/sites/default/includes/modules`. The per-module files add the development modules there on purpose, because provisioning installs them and a configuration import never does. Don't export their configuration into `config/default`.
+- An excluded module owns the item. Modules in `$settings['config_exclude_modules']` stay out of the export, and so does their configuration. Check: `grep -rn config_exclude_modules docroot/sites/default/includes/modules`. The per-module files add the development modules there on purpose, because provisioning installs them and a configuration import never does. Don't export their configuration into `config/default`.
 - The item never reached the database. Someone added the file without importing it, and a full export then removes it. Check: `ahoy drush config:get <name>`. Fix: restore the file with `git restore <path>`, then import it with a partial import.
 - A configuration entity was deleted in the admin UI. The export deletes its file, which is the correct result when the deletion was intended.
 
 - A split's `complete_list`, `module` or `theme` list holds the item, or holds the module that provides it. The export moves the item into the split's directory, such as `config/local`, and deletes it from `config/default`. Check: `grep -n -A20 complete_list config/default/config_split.config_split.local.yml`, and the same for the `module` list and for each split. Fix: when the item must exist in every environment with different values, move it from `complete_list` to `partial_list`, then run a full export. `config/default/<name>.yml` comes back, and the split directory gets `config_split.patch.<name>.yml`.
-- The split is active by accident. A definition file that says `status: true` is active in every environment. Check: `ahoy drush config:get config_split.config_split.<split> status`. Fix: set the stored status to `false`, and let `web/sites/default/includes/modules/settings.config_split.php` turn the split on for its environment.
+- The split is active by accident. A definition file that says `status: true` is active in every environment. Check: `ahoy drush config:get config_split.config_split.<split> status`. Fix: set the stored status to `false`, and let `docroot/sites/default/includes/modules/settings.config_split.php` turn the split on for its environment.
 
 ## An import fails
 
@@ -41,4 +41,4 @@
 
 `config:status` reports no difference, yet the page or the admin form shows another value. A settings override changes the value at runtime without touching the stored configuration.
 
-Check: compare `ahoy drush config:get <name> <key>` with `ahoy drush config:get <name> <key> --include-overridden`, then search the overrides: `grep -rn "config\['<name>'\]" web/sites/default`. Fix: change the override file, not the configuration. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides).
+Check: compare `ahoy drush config:get <name> <key>` with `ahoy drush config:get <name> <key> --include-overridden`, then search the overrides: `grep -rn "config\['<name>'\]" docroot/sites/default`. Fix: change the override file, not the configuration. See [Per-module overrides](https://www.vortextemplate.com/docs/development/settings#per-module-overrides).
