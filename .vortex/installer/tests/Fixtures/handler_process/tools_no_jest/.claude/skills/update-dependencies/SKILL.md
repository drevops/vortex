@@ -141,8 +141,6 @@
 
 - `ahoy test-bdd`, which needs the provisioned site
 
-- `ahoy test-js`
-
 Rector resolves its Drupal sets from the installed `drupal/core` version, so a core update can bring new findings. After updating the Rector packages, clear its cache: `ahoy cli vendor/bin/rector process --clear-cache --dry-run`
 
 Rebuild the stack with `ahoy build` only when the update changes the container images and the developer agrees, because it removes the containers and the database. When the stack or provisioning fails, use the `set-up-environment` skill. When the site shows errors after the update, use the `debug-errors` skill.
