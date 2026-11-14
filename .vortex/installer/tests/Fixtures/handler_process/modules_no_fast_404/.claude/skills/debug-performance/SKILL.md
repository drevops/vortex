@@ -117,8 +117,6 @@
 - Read the web server and PHP logs with `ahoy logs -- --tail=50 nginx` and `ahoy logs -- --tail=50 php`, and Drupal's log with `ahoy drush watchdog:show --count=20`.
 - In hosted environments, Automated Cron stays on; `web/sites/default/includes/modules/settings.automated_cron.php` turns it off only in `local` and `ci`. A regular spike in load can be cron, so check `ahoy drush watchdog:show --type=cron` before profiling a page.
 
-- Requests for missing static files with the extensions listed in `web/sites/default/includes/modules/settings.fast_404.php` get a 404 before Drupal bootstraps. Missing image style derivatives still reach Drupal, which generates them on first request.
-
 ## Devel
 
 Provisioning installs Devel in the `local`, `ci`, `dev` and `stage` environments (`scripts/provision-10-enable-dev-modules.sh`), and `settings.devel.php` keeps it out of the exported configuration. Devel has no query log, so count queries with the request probe. Use its pages to check what the running site loaded:
