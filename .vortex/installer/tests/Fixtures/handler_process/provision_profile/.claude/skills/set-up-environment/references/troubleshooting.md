@@ -72,12 +72,7 @@
 
 The local site lacks content or configuration that the hosting environment has, or `ahoy fetch-db` printed `Fetch will not proceed.`
 
-1. Check the age of the dump: `ls -l .data`.
-2. Fetch a new copy: `ahoy fetch-db --fresh`. On a hosting source, this also creates a new backup or dump at the source.
-3. Load it, after asking: `VORTEX_PROVISION_OVERRIDE_DB=1 AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy provision`. Without `VORTEX_PROVISION_OVERRIDE_DB=1`, provisioning keeps the installed site's database and prints `Site content will be preserved.`
-4. Confirm that the provisioning output lists `Overwrite existing DB : Yes` and ends with `Finished site provisioning`.
-
-A fresh dump from a hosting environment is sanitized during provisioning, so user email addresses and passwords differ from production. Log in with `ahoy login`.
+This project installs from a profile, so there is no dump to refresh. Reinstall the site from the profile and the files in `config/default`, after asking: `VORTEX_PROVISION_OVERRIDE_DB=1 AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy provision`.
 
 ## Dependencies are out of date
 
