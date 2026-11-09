@@ -74,17 +74,17 @@
 
 - Accept uploads through the `managed_file` element or the file upload service, with `#upload_validators` that list `FileExtension` and `FileSizeLimit`. Never allow executable or script extensions, and leave the `allow_insecure_uploads` setting off.
 - Treat SVG as active content. Accept SVG uploads only from trusted roles.
-- Store anything that is not meant for the public in the `private://` scheme through `#upload_location`. **Vortex** sets the private path from the `DRUPAL_PRIVATE_FILES` variable, `sites/default/files/private` by default, in `web/sites/default/settings.php`.
+- Store anything that is not meant for the public in the `private://` scheme through `#upload_location`. **Vortex** sets the private path from the `DRUPAL_PRIVATE_FILES` variable, `sites/default/files/private` by default, in `docroot/sites/default/settings.php`.
 - Serve private files through Drupal's `/system/files/` route. For a file referenced by a file field, Drupal checks access to the entity that references it. For a file the code manages on its own, implement `hook_file_download()` and return `-1` for users without access.
 - The default private path sits inside the public files directory, so check that the web server refuses a direct request: fetch `/sites/default/files/private/<file>` and expect a 403 or 404, then fetch `/system/files/<file>` as a user with and without access.
 - Never write uploaded bytes straight to disk with `file_put_contents()` or `move_uploaded_file()`. That skips every upload validator.
 
-- **Vortex** ships the ClamAV integration: `web/sites/default/includes/modules/settings.clamav.php` points the module at the `clamav` container in daemon mode when `DRUPAL_CLAMAV_ENABLED` is `1`, which the shipped `.env` sets. The scan runs as an upload validator, so route every upload through Drupal's file validation, or the files skip the virus scan too.
+- **Vortex** ships the ClamAV integration: `docroot/sites/default/includes/modules/settings.clamav.php` points the module at the `clamav` container in daemon mode when `DRUPAL_CLAMAV_ENABLED` is `1`, which the shipped `.env` sets. The scan runs as an upload validator, so route every upload through Drupal's file validation, or the files skip the virus scan too.
 
 ## Secrets and credentials
 
 - Never put a password, token, API key or private key in code, in exported configuration under `config/default`, in the committed `.env` file, in test fixtures or in a commit message.
-- Read each secret from an environment variable with the `DRUPAL_` prefix, in a per-module override file such as `web/sites/default/includes/modules/settings.sw_base.php`. Store it in `$settings`, guarded with `!empty()`, and read it with `Settings::get()` through the injected `settings` service. The settings guidelines on [Settings](https://www.vortextemplate.com/docs/development/settings#guidelines) cover the file layout.
+- Read each secret from an environment variable with the `DRUPAL_` prefix, in a per-module override file such as `docroot/sites/default/includes/modules/settings.sw_base.php`. Store it in `$settings`, guarded with `!empty()`, and read it with `Settings::get()` through the injected `settings` service. The settings guidelines on [Settings](https://www.vortextemplate.com/docs/development/settings#guidelines) cover the file layout.
 - Keep secrets out of `$config` overrides and configuration forms. A form saves whatever it shows into active configuration, and `ahoy drush cex` then writes it to `config/default`.
 - Fail closed: when the variable is empty, skip the integration and log that it isn't configured, without the value.
 - Locally, put the value in `.env.local`, which Git ignores. `ahoy drush` and `ahoy cli` forward `DRUPAL_*` variables from it. Web requests don't read `.env.local`, so also pass the variable to the `php` service in `docker-compose.override.yml` and run `ahoy up`, as [Environment variables](https://www.vortextemplate.com/docs/development/environment#environment-variables) describes.
@@ -104,12 +104,12 @@
 - Never log passwords, tokens, API keys, session IDs, full request or response bodies, or personal data such as email addresses. Log an entity ID instead.
 - Log exceptions with `Error::logException($this->logger, $exception)`, after checking that the exception message can't hold a secret.
 - Show users a generic message through the messenger service and keep the details in the log.
-- Leave error display as the environment sets it. **Vortex** shows all errors only in `local` and `ci`, in `web/sites/default/includes/modules/settings.system.php`.
+- Leave error display as the environment sets it. **Vortex** shows all errors only in `local` and `ci`, in `docroot/sites/default/includes/modules/settings.system.php`.
 - Check what the code logs with `ahoy drush watchdog:show --count=20`.
 
 ## Security headers
 
-The Security Kit module ships with the project but provisioning doesn't install it. `web/sites/default/includes/modules/settings.seckit.php` turns off its Content Security Policy locally and in CI, where the site isn't served over HTTPS. Write front-end code that works under a strict policy whether or not the module is on yet: no inline `<script>` blocks, no inline event handlers such as `onclick`, and JavaScript attached through a library and `Drupal.behaviors`. See [Security Kit](https://www.vortextemplate.com/docs/development/modules/contributed-modules#security-kit).
+The Security Kit module ships with the project but provisioning doesn't install it. `docroot/sites/default/includes/modules/settings.seckit.php` turns off its Content Security Policy locally and in CI, where the site isn't served over HTTPS. Write front-end code that works under a strict policy whether or not the module is on yet: no inline `<script>` blocks, no inline event handlers such as `onclick`, and JavaScript attached through a library and `Drupal.behaviors`. See [Security Kit](https://www.vortextemplate.com/docs/development/modules/contributed-modules#security-kit).
 
 ## Dependencies
 
@@ -120,7 +120,7 @@
 
 - Audit the custom theme's packages with `ahoy cli "npm audit --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`.
 
-- Apply an upstream security fix to a contributed package as a patch through `cweagans/composer-patches`, with the patch file in `patches/`. Never edit files under `vendor/` or `web/modules/contrib/` directly.
+- Apply an upstream security fix to a contributed package as a patch through `cweagans/composer-patches`, with the patch file in `patches/`. Never edit files under `vendor/` or `docroot/modules/contrib/` directly.
 
 ## Finish
 
