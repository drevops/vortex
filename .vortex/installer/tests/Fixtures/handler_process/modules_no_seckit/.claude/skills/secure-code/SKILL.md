@@ -107,10 +107,6 @@
 - Leave error display as the environment sets it. **Vortex** shows all errors only in `local` and `ci`, in `web/sites/default/includes/modules/settings.system.php`.
 - Check what the code logs with `ahoy drush watchdog:show --count=20`.
 
-## Security headers
-
-The Security Kit module ships with the project but provisioning doesn't install it. `web/sites/default/includes/modules/settings.seckit.php` turns off its Content Security Policy locally and in CI, where the site isn't served over HTTPS. Write front-end code that works under a strict policy whether or not the module is on yet: no inline `<script>` blocks, no inline event handlers such as `onclick`, and JavaScript attached through a library and `Drupal.behaviors`. See [Security Kit](https://www.vortextemplate.com/docs/development/modules/contributed-modules#security-kit).
-
 ## Dependencies
 
 - Add packages with `ahoy composer require <vendor/package>`, then run `ahoy composer audit`.
