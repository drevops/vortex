@@ -113,20 +113,6 @@
 
 After the update, run `ahoy fei` to install from the lock files, and `ahoy fe` to rebuild the theme's assets.
 
-## Automated update pull requests
-
-Renovate opens update pull requests from branches with the `deps/` prefix, labeled `Dependencies`. Security fixes also carry the `Security` label. Read [references/renovate.md](references/renovate.md) for the groups, schedules and delays in `renovate.json`, and for what Renovate leaves to be updated by hand.
-
-To review or fix a Renovate pull request:
-
-1. With the pull request's `deps/` branch checked out, install its packages: `ahoy composer install`
-2. Run [Verify the update](#verify-the-update) on it.
-3. Renovate changes only the package files. When the update needs a re-rolled patch or exported configuration, commit that to the same branch.
-
-Renovate doesn't run database updates or export configuration. The CI build provisions the site, which imports the configuration right after the updates run, so an update hook's configuration change is lost without failing the build, unless `VORTEX_PROVISION_VERIFY_CONFIG_UNCHANGED_AFTER_UPDATE` is `1`. Check every update that ships update hooks with `ahoy drush config:status`, as [Run database updates and export configuration](#run-database-updates-and-export-configuration) describes.
-
-Renovate runs as the hosted Renovate GitHub app. To raise security fixes, the app needs read access to Dependabot alerts, which the repository owner checks under **Settings > GitHub Apps**.
-
 ## Verify the update
 
 1. Review the changed files: `git status --short`. Expect `composer.json`, `composer.lock`, `patches.lock.json`, the npm manifests and lock files, and exported configuration in `config/`.
