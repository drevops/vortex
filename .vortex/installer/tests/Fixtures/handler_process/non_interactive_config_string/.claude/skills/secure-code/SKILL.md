@@ -79,8 +79,6 @@
 - The default private path sits inside the public files directory, so check that the web server refuses a direct request: fetch `/sites/default/files/private/<file>` and expect a 403 or 404, then fetch `/system/files/<file>` as a user with and without access.
 - Never write uploaded bytes straight to disk with `file_put_contents()` or `move_uploaded_file()`. That skips every upload validator.
 
-- **Vortex** ships the ClamAV integration: `web/sites/default/includes/modules/settings.clamav.php` points the module at the `clamav` container in daemon mode when `DRUPAL_CLAMAV_ENABLED` is `1`, which the shipped `.env` sets. The scan runs as an upload validator, so route every upload through Drupal's file validation, or the files skip the virus scan too.
-
 ## Secrets and credentials
 
 - Never put a password, token, API key or private key in code, in exported configuration under `config/default`, in the committed `.env` file, in test fixtures or in a commit message.
