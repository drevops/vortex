@@ -95,8 +95,6 @@
 
 - The Shield credentials come from `DRUPAL_SHIELD_USER` and `DRUPAL_SHIELD_PASS`, set per environment, never in `.env`. Shield hides `dev` and `stage` from the public; it is not access control for the site, so never rely on it to protect a route.
 
-- CI scans the code for committed secrets. When a real secret reaches a commit, rotate it with the service that issued it; deleting the line or rewriting history leaves it exposed. Use the `gitleaks:allow` comment only for values that are not secrets. See [Secret scanning](https://www.vortextemplate.com/docs/development/security/secret-scanning).
-
 ## Logging without leaking data
 
 - Log through an injected logger channel named after the module, such as one from `logger.factory` with `get('sw_base')`.
