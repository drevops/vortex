@@ -56,16 +56,6 @@
 
 To disable, run `ahoy up`.
 
-## How to use Xdebug on Behat scripts?
-
-1. Enable debugging: `ahoy debug`
-2. Enter CLI container: `ahoy cli`
-3. Run Behat tests:
-
-```bash
-vendor/bin/behat path/to/test.feature
-```
-
 ## What should I do to switch to a "clean" branch environment?
 
 Provided that your stack is already running:
@@ -130,10 +120,3 @@
 This theme will be used when Drupal is in maintenance mode. If `DRUPAL_MAINTENANCE_THEME` is not set, the system will fall back to using the value of `DRUPAL_THEME`.
 
 The maintenance theme should be a valid Drupal theme that is already installed and enabled on your site.
-
-## Behat tests with `@javascript` tag sometimes get stuck
-
-Behat tests with `@javascript` tag sometimes get stuck for about 10min then fail.
-The Chrome container randomly get stuck for an unknown reason.
-
-Restart the Chrome container: `docker compose restart chrome`
