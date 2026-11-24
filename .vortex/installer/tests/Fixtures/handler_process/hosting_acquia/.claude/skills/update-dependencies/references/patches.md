@@ -12,7 +12,7 @@
 
 1. Delete the entry from `extra.patches` in `composer.json`. Delete its file from `patches/` when no other entry uses it.
 2. `ahoy composer patches-relock`
-3. Delete the package's directory, because `patches-repatch` reinstalls only the packages that still have patches. The directory follows `installer-paths` in `composer.json`, such as `web/modules/contrib/<name>` for a module or `vendor/<vendor>/<package>` for a library: `ahoy cli rm -rf web/modules/contrib/<name>`
+3. Delete the package's directory, because `patches-repatch` reinstalls only the packages that still have patches. The directory follows `installer-paths` in `composer.json`, such as `docroot/modules/contrib/<name>` for a module or `vendor/<vendor>/<package>` for a library: `ahoy cli rm -rf docroot/modules/contrib/<name>`
 4. `ahoy composer patches-repatch`
 5. `ahoy composer update --lock`
 6. Confirm that `patches.lock.json` lists the package only when it still has other patches.
