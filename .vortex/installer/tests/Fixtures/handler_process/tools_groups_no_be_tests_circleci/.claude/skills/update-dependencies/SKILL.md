@@ -137,10 +137,6 @@
 6. Run `ahoy lint` and fix what it reports.
 7. Run every test suite that the project has.
 
-- `ahoy test`
-
-- `ahoy test-bdd`, which needs the provisioned site
-
 - `ahoy test-js`
 
 Rector resolves its Drupal sets from the installed `drupal/core` version, so a core update can bring new findings. After updating the Rector packages, clear its cache: `ahoy cli vendor/bin/rector process --clear-cache --dry-run`
