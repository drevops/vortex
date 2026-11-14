@@ -52,7 +52,7 @@
 A `SKIPPED` line means a full export wouldn't write that item to `config/default` either. Find out why before you work around it:
 
 - The database doesn't hold the item: `ahoy drush config:get <name>` fails with `Config <name> does not exist`.
-- An excluded module owns it: the module appears in a `config_exclude_modules` line in `web/sites/default/includes/modules/settings.<module>.php`.
+- An excluded module owns it: the module appears in a `config_exclude_modules` line in `docroot/sites/default/includes/modules/settings.<module>.php`.
 - An active configuration split owns it: the item is listed in that split's definition, and a full export writes it to the split's directory.
 
 The script writes only to the sync directory. It never removes a file, so an item deleted from the database still needs its file removed by hand, after the developer confirmed.
