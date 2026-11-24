@@ -16,6 +16,8 @@
 - When `web/sites/default/settings.local.php` exists, it may map the `render`, `page` and `dynamic_page_cache` bins to `cache.backend.null`, as `example.settings.local.php` does. Every request is then a MISS. For the investigation, comment out those 3 lines in `settings.local.php`, which Git ignores, and keep `services.local.yml` loaded: it sets `http.response.debug_cacheability_headers: true`, which adds the debug headers in step 2. Don't use `DRUPAL_SETTINGS_LOCAL_SKIP` for this, because it skips `services.local.yml` too.
 - Check **Do not cache markup** on `/admin/config/development/settings`. When it's on, Drupal disables the same 3 bins.
 
+- Provisioning turns **Do not cache markup** on in the `local` environment, in `scripts/provision-10-enable-dev-modules.sh`, so Storybook shows template edits without a cache rebuild. Turn it off for the investigation and back on afterwards.
+
 - Run the request probe and confirm that no bin in its first lines reports `NullBackend`.
 - Turn Xdebug off with `ahoy up` if `ahoy info` shows it on. It slows every request.
 - Measure on a database with production-sized content. A page that is fast with 20 nodes can be slow with 20,000. Ask the developer before running `ahoy provision`, which replaces the local database.
@@ -118,14 +120,6 @@
 - In hosted environments, Automated Cron stays on; `web/sites/default/includes/modules/settings.automated_cron.php` turns it off only in `local` and `ci`. A regular spike in load can be cron, so check `ahoy drush watchdog:show --type=cron` before profiling a page.
 
 - Requests for missing static files with the extensions listed in `web/sites/default/includes/modules/settings.fast_404.php` get a 404 before Drupal bootstraps. Missing image style derivatives still reach Drupal, which generates them on first request.
-
-## Devel
-
-Provisioning installs Devel in the `local`, `ci`, `dev` and `stage` environments (`scripts/provision-10-enable-dev-modules.sh`), and `settings.devel.php` keeps it out of the exported configuration. Devel has no query log, so count queries with the request probe. Use its pages to check what the running site loaded:
-
-- `/devel/container/parameter` shows the effective `renderer.config` and `http.response.debug_cacheability_headers`, so it confirms that `services.local.yml` applied.
-- `/devel/events` lists event subscribers, such as a response subscriber that sets cache headers or calls the kill switch.
-- `/devel/routes` shows each route's path, controller and requirements.
 
 ## Finish
 
