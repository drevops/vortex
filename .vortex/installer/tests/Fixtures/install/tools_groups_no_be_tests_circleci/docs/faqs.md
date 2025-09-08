@@ -58,16 +58,6 @@
 
 See https://www.vortextemplate.com/docs/tools/xdebug for more details.
 
-## How to use Xdebug on Behat scripts?
-
-1. Enable debugging: `ahoy debug`
-2. Enter CLI container: `ahoy cli`
-3. Run Behat tests:
-
-```bash
-vendor/bin/behat --xdebug path/to/test.feature
-```
-
 ## What should I do to switch to a "clean" branch environment?
 
 Provided that your stack is already running:
@@ -139,11 +129,3 @@
 
 The maintenance theme should be a valid Drupal theme that is already installed
 and enabled on your site.
-
-## Behat tests with `@javascript` tag sometimes get stuck
-
-Behat tests with `@javascript` tag sometimes get stuck for about 10min then
-fail.
-The Chrome container randomly get stuck for an unknown reason.
-
-Restart the Chrome container: `docker compose restart chrome`
