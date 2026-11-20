@@ -65,7 +65,7 @@
 
 - The URL is `http://<COMPOSE_PROJECT_NAME>.docker.amazee.io`, and the project name defaults to the project directory name. Read the exact URL from `ahoy info`. `LOCALDEV_URL` in `.env.local` overrides it.
 - The URL doesn't resolve: Pygmy's DNS or proxy isn't running. Run `pygmy status`, then `pygmy up`.
-- Drupal answers `The provided host name is not valid for this server.`: the request used a host name that isn't trusted. `web/sites/default/includes/providers/settings.container.php` trusts the host in `LOCALDEV_URL` and `nginx`, `settings.php` trusts `localhost`, and `settings.trusted_hosts.php` adds the domains in `DRUPAL_TRUSTED_HOSTS`. Use the URL from `ahoy info`.
+- Drupal answers `The provided host name is not valid for this server.`: the request used a host name that isn't trusted. `docroot/sites/default/includes/providers/settings.container.php` trusts the host in `LOCALDEV_URL` and `nginx`, `settings.php` trusts `localhost`, and `settings.trusted_hosts.php` adds the domains in `DRUPAL_TRUSTED_HOSTS`. Use the URL from `ahoy info`.
 - The page loads with an error: use the `debug-errors` skill.
 
 ## The database dump is stale
@@ -89,7 +89,7 @@
 
 When the root `package.json` or `package-lock.json` changed, run `ahoy fei`. It runs `npm ci`, which installs exactly what the lock file lists and fails when the lock file and `package.json` disagree. A failure there means the lock file in the commit is out of date, so tell the developer instead of regenerating it.
 
-When files in `web/themes/custom/<theme>` changed, run `ahoy fei` for its npm packages and then `ahoy fe` to rebuild its assets. Unstyled pages after a pull usually mean the assets weren't rebuilt.
+When files in `docroot/themes/custom/<theme>` changed, run `ahoy fei` for its npm packages and then `ahoy fe` to rebuild its assets. Unstyled pages after a pull usually mean the assets weren't rebuilt.
 
 ## A variable doesn't apply
 
