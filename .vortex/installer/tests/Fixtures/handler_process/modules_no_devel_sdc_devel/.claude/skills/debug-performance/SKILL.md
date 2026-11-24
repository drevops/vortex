@@ -119,14 +119,6 @@
 
 - Requests for missing static files with the extensions listed in `web/sites/default/includes/modules/settings.fast_404.php` get a 404 before Drupal bootstraps. Missing image style derivatives still reach Drupal, which generates them on first request.
 
-## Devel
-
-Provisioning installs Devel in the `local`, `ci`, `dev` and `stage` environments (`scripts/provision-10-enable-dev-modules.sh`), and `settings.devel.php` keeps it out of the exported configuration. Devel has no query log, so count queries with the request probe. Use its pages to check what the running site loaded:
-
-- `/devel/container/parameter` shows the effective `renderer.config` and `http.response.debug_cacheability_headers`, so it confirms that `services.local.yml` applied.
-- `/devel/events` lists event subscribers, such as a response subscriber that sets cache headers or calls the kill switch.
-- `/devel/routes` shows each route's path, controller and requirements.
-
 ## Finish
 
 1. Run the same measurement from step 2 again, cold and warm, and compare it with the first run.
