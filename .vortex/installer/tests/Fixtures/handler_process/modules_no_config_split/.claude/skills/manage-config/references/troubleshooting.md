@@ -10,9 +10,6 @@
 - The item never reached the database. Someone added the file without importing it, and a full export then removes it. Check: `ahoy drush config:get <name>`. Fix: restore the file with `git restore <path>`, then import it with a partial import.
 - A configuration entity was deleted in the admin UI. The export deletes its file, which is the correct result when the deletion was intended.
 
-- A split's `complete_list`, `module` or `theme` list holds the item, or holds the module that provides it. The export moves the item into the split's directory, such as `config/local`, and deletes it from `config/default`. Check: `grep -n -A20 complete_list config/default/config_split.config_split.local.yml`, and the same for the `module` list and for each split. Fix: when the item must exist in every environment with different values, move it from `complete_list` to `partial_list`, then run a full export. `config/default/<name>.yml` comes back, and the split directory gets `config_split.patch.<name>.yml`.
-- The split is active by accident. A definition file that says `status: true` is active in every environment. Check: `ahoy drush config:get config_split.config_split.<split> status`. Fix: set the stored status to `false`, and let `web/sites/default/includes/modules/settings.config_split.php` turn the split on for its environment.
-
 ## An import fails
 
 Drush prints the validation errors from Drupal and imports nothing. Read the first error and match it:
