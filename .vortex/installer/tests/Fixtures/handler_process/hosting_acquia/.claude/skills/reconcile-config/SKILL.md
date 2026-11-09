@@ -29,6 +29,8 @@
 
 Then work out the environment type that the environment runs as, `prod`, `stage` or `dev`. Step 4 runs the comparison with it. See [Environment type detection](https://www.vortextemplate.com/docs/development/settings#environment-type-detection).
 
+On Acquia, the source is `acquia`, and the command downloads the latest backup of the `VORTEX_FETCH_DB_ACQUIA_DB_NAME` database. `docroot/sites/default/includes/providers/settings.acquia.php` maps `prod` to `prod`, `stage` and `test` to `stage`, and every other environment to `dev`.
+
 ## 3. Fetch and import the database
 
 1. `ahoy fetch-db` keeps an existing `.data/db.sql` whatever its age, so the comparison could run against old data. Download a new copy with `VORTEX_FETCH_DB_FORCE=1 ahoy fetch-db`. Confirm that the output ends with `Finished database fetch.`
@@ -43,7 +45,7 @@
 
 Confirm the type: copy the script from [references/verify.md](references/verify.md) to `.artifacts/tmp/reconcile-config/verify.php` and run it without names, `ENVIRONMENT_TYPE=prod ahoy drush php:script /app/.artifacts/tmp/reconcile-config/verify.php`. The first line reads `Environment type: prod`.
 
-Under the `local` type, `web/sites/default/includes/modules/settings.config_split.php` turns on the `local` split. The comparison then applies that split, so its modules and items show up as `Only in sync dir`, and `core.extension` as `Different`, although the environment never had them. Under the environment's type, the comparison applies the split the environment uses: `stage` or `dev`, and none for `prod`.
+Under the `local` type, `docroot/sites/default/includes/modules/settings.config_split.php` turns on the `local` split. The comparison then applies that split, so its modules and items show up as `Only in sync dir`, and `core.extension` as `Different`, although the environment never had them. Under the environment's type, the comparison applies the split the environment uses: `stage` or `dev`, and none for `prod`.
 
 Check a split with `ENVIRONMENT_TYPE=prod ahoy drush config:get config_split.config_split.local status --include-overridden`, which reports `false` when the split is off. A split whose definition file says `status: true` is on under every type. Tell the developer, because the environment applies it too.
 
@@ -112,3 +114,7 @@
 Print a table with 1 row per item: name, state, decision, reason and result. Below it, show `git status --short config/` and `git diff --stat config/`. Don't commit or push. Remind the developer that Import items reach the environment only when this code deploys and provisioning runs the configuration import.
 
 Then offer to restore the local site. After the developer agrees, run `AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy import-db .data/export_db_<timestamp>.sql` with the file from step 1, or `AHOY_CONFIRM_RESPONSE=y ahoy provision` for a fresh, fully configured site. Finally, remove the scratch files with `rm -rf .artifacts/tmp/reconcile-config`.
+
+## Cross-check an Acquia environment
+
+The template ships no Drush aliases for Acquia. The developer downloads them from the Acquia Cloud UI, as [Run Drush against remote environments](https://www.vortextemplate.com/docs/hosting/acquia#run-drush-against-remote-environments) describes. With the aliases in place, run only `config:get <name>` and `config:status` against an environment. Never run a command that writes.
