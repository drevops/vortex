@@ -85,10 +85,10 @@
 | `all`     | Every message                                  |
 | `verbose` | Every message, with a backtrace                |
 
-- `web/sites/default/includes/modules/settings.system.php` sets `all` in the `local` and `ci` environment types.
-- A `web/sites/default/settings.local.php` copied from `example.settings.local.php` sets `verbose`. `settings.php` loads it last, so its value wins.
+- `docroot/sites/default/includes/modules/settings.system.php` sets `all` in the `local` and `ci` environment types.
+- A `docroot/sites/default/settings.local.php` copied from `example.settings.local.php` sets `verbose`. `settings.php` loads it last, so its value wins.
 - Both are configuration overrides, so the setting on `/admin/config/development/logging` changes nothing locally. Read the value the site uses with `ahoy drush config:get system.logging error_level --include-overridden`.
-- To get backtraces on the page, the developer copies `example.settings.local.php` to `settings.local.php` and `example.services.local.yml` to `services.local.yml` in `web/sites/default`. Git ignores both. Ask first, because these files also bypass the render, page and dynamic page caches, turn on Twig debugging and allow `/core/rebuild.php`.
+- To get backtraces on the page, the developer copies `example.settings.local.php` to `settings.local.php` and `example.services.local.yml` to `services.local.yml` in `docroot/sites/default`. Git ignores both. Ask first, because these files also bypass the render, page and dynamic page caches, turn on Twig debugging and allow `/core/rebuild.php`.
 - The settings probe in `references/probes.md` prints the environment type, both error levels, the loaded service files and the cache backends at once.
 
 ## White screens and error responses
