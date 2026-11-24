@@ -57,8 +57,6 @@
 
 Hosted environments send no debug headers, so read `X-Drupal-Cache`, `X-Drupal-Dynamic-Cache` and `Cache-Control` there.
 
-On `dev` and `stage`, Shield asks for credentials first. Add `-u "USER:PASS"` with the values of `DRUPAL_SHIELD_USER` and `DRUPAL_SHIELD_PASS` from that environment.
-
 ## 3. Decide which layer is slow
 
 - **Slow first byte, high query count**: the render path. Go to step 4 for caching and step 5 for queries.
@@ -116,16 +114,6 @@
 - A 503 with no `X-Drupal-*` headers comes from `maintenance.conf`, served while `VORTEX_WEBSERVER_MAINTENANCE_MODE` is `1`. It isn't a slow Drupal response.
 - Read the web server and PHP logs with `ahoy logs -- --tail=50 nginx` and `ahoy logs -- --tail=50 php`, and Drupal's log with `ahoy drush watchdog:show --count=20`.
 - In hosted environments, Automated Cron stays on; `web/sites/default/includes/modules/settings.automated_cron.php` turns it off only in `local` and `ci`. A regular spike in load can be cron, so check `ahoy drush watchdog:show --type=cron` before profiling a page.
-
-- Requests for missing static files with the extensions listed in `web/sites/default/includes/modules/settings.fast_404.php` get a 404 before Drupal bootstraps. Missing image style derivatives still reach Drupal, which generates them on first request.
-
-## Devel
-
-Provisioning installs Devel in the `local`, `ci`, `dev` and `stage` environments (`scripts/provision-10-enable-dev-modules.sh`), and `settings.devel.php` keeps it out of the exported configuration. Devel has no query log, so count queries with the request probe. Use its pages to check what the running site loaded:
-
-- `/devel/container/parameter` shows the effective `renderer.config` and `http.response.debug_cacheability_headers`, so it confirms that `services.local.yml` applied.
-- `/devel/events` lists event subscribers, such as a response subscriber that sets cache headers or calls the kill switch.
-- `/devel/routes` shows each route's path, controller and requirements.
 
 ## Finish
 
