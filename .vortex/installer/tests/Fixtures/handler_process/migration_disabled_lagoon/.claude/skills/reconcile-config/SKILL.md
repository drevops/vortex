@@ -29,6 +29,12 @@
 
 Then work out the environment type that the environment runs as, `prod`, `stage` or `dev`. Step 4 runs the comparison with it. See [Environment type detection](https://www.vortextemplate.com/docs/development/settings#environment-type-detection).
 
+On Lagoon, the installer sets the source to `lagoon` and the environment to `main`. `web/sites/default/includes/providers/settings.lagoon.php` maps the environment to a type:
+
+- `prod`: the Lagoon environment type is `production`, or the branch matches `VORTEX_LAGOON_PRODUCTION_BRANCH`, which `.env` sets to `main`
+- `stage`: a `main` or `master` branch that isn't the production branch, and `release/*` and `hotfix/*` branches
+- `dev`: every other branch and pull request environment
+
 ## 3. Fetch and import the database
 
 1. `ahoy fetch-db` keeps an existing `.data/db.sql` whatever its age, so the comparison could run against old data. Download a new copy with `VORTEX_FETCH_DB_FORCE=1 ahoy fetch-db`. Confirm that the output ends with `Finished database fetch.`
@@ -112,3 +118,12 @@
 Print a table with 1 row per item: name, state, decision, reason and result. Below it, show `git status --short config/` and `git diff --stat config/`. Don't commit or push. Remind the developer that Import items reach the environment only when this code deploys and provisioning runs the configuration import.
 
 Then offer to restore the local site. After the developer agrees, run `AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy import-db .data/export_db_<timestamp>.sql` with the file from step 1, or `AHOY_CONFIRM_RESPONSE=y ahoy provision` for a fresh, fully configured site. Finally, remove the scratch files with `rm -rf .artifacts/tmp/reconcile-config`.
+
+## Cross-check a Lagoon environment
+
+Reading from the environment itself can confirm that the dump isn't stale. `drush/sites/lagoon.site.yml` defines an alias for every environment, such as `@lagoon.main`. Load your SSH key first, as [Drush aliases](https://www.vortextemplate.com/docs/development/environment/drush#aliases) describes.
+
+- `ahoy drush @lagoon.<environment> config:get <name>` prints the environment's current value.
+- `ahoy drush @lagoon.<environment> config:status` compares the environment's database with the code deployed there, not with this branch.
+
+Leave out the `ENVIRONMENT_TYPE` prefix here, because the environment detects its own type. Run only these read-only commands against an alias. Never run `config:import`, `config:export`, `config:set`, `config:delete`, `cache:rebuild` or any other command that writes. When the alias doesn't connect, check that the project name in the alias file matches `LAGOON_PROJECT` in `.env`.
