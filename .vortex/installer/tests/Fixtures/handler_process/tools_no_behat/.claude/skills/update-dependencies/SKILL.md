@@ -139,8 +139,6 @@
 
 - `ahoy test`
 
-- `ahoy test-bdd`, which needs the provisioned site
-
 - `ahoy test-js`
 
 Rector resolves its Drupal sets from the installed `drupal/core` version, so a core update can bring new findings. After updating the Rector packages, clear its cache: `ahoy cli vendor/bin/rector process --clear-cache --dry-run`
