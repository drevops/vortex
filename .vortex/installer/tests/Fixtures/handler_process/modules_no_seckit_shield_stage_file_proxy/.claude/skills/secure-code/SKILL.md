@@ -93,8 +93,6 @@
 - Send API keys in request headers, not in query strings. HTTP client exceptions include the request URL in their message, so a key in the URL ends up in the logs.
 - Never expose a secret through `drupalSettings`, a render array, an error message or command output.
 
-- The Shield credentials come from `DRUPAL_SHIELD_USER` and `DRUPAL_SHIELD_PASS`, set per environment, never in `.env`. Shield hides `dev` and `stage` from the public; it is not access control for the site, so never rely on it to protect a route.
-
 - CI scans the code for committed secrets. When a real secret reaches a commit, rotate it with the service that issued it; deleting the line or rewriting history leaves it exposed. Use the `gitleaks:allow` comment only for values that are not secrets. See [Secret scanning](https://www.vortextemplate.com/docs/development/security/secret-scanning).
 
 ## Logging without leaking data
@@ -106,10 +104,6 @@
 - Show users a generic message through the messenger service and keep the details in the log.
 - Leave error display as the environment sets it. **Vortex** shows all errors only in `local` and `ci`, in `web/sites/default/includes/modules/settings.system.php`.
 - Check what the code logs with `ahoy drush watchdog:show --count=20`.
-
-## Security headers
-
-The Security Kit module ships with the project but provisioning doesn't install it. `web/sites/default/includes/modules/settings.seckit.php` turns off its Content Security Policy locally and in CI, where the site isn't served over HTTPS. Write front-end code that works under a strict policy whether or not the module is on yet: no inline `<script>` blocks, no inline event handlers such as `onclick`, and JavaScript attached through a library and `Drupal.behaviors`. See [Security Kit](https://www.vortextemplate.com/docs/development/modules/contributed-modules#security-kit).
 
 ## Dependencies
 
