@@ -16,6 +16,8 @@
 - When `web/sites/default/settings.local.php` exists, it may map the `render`, `page` and `dynamic_page_cache` bins to `cache.backend.null`, as `example.settings.local.php` does. Every request is then a MISS. For the investigation, comment out those 3 lines in `settings.local.php`, which Git ignores, and keep `services.local.yml` loaded: it sets `http.response.debug_cacheability_headers: true`, which adds the debug headers in step 2. Don't use `DRUPAL_SETTINGS_LOCAL_SKIP` for this, because it skips `services.local.yml` too.
 - Check **Do not cache markup** on `/admin/config/development/settings`. When it's on, Drupal disables the same 3 bins.
 
+- Provisioning turns **Do not cache markup** on in the `local` environment, in `scripts/provision-10-enable-dev-modules.sh`, so Storybook shows template edits without a cache rebuild. Turn it off for the investigation and back on afterwards.
+
 - Run the request probe and confirm that no bin in its first lines reports `NullBackend`.
 - Turn Xdebug off with `ahoy up` if `ahoy info` shows it on. It slows every request.
 - Measure on a database with production-sized content. A page that is fast with 20 nodes can be slow with 20,000. Ask the developer before running `ahoy provision`, which replaces the local database.
