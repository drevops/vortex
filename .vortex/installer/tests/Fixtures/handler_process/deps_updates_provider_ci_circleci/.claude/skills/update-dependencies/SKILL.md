@@ -125,7 +125,9 @@
 
 Renovate doesn't run database updates or export configuration. The CI build provisions the site, which imports the configuration right after the updates run, so an update hook's configuration change is lost without failing the build, unless `VORTEX_PROVISION_VERIFY_CONFIG_UNCHANGED_AFTER_UPDATE` is `1`. Check every update that ships update hooks with `ahoy drush config:status`, as [Run database updates and export configuration](#run-database-updates-and-export-configuration) describes.
 
-Renovate runs as the hosted Renovate GitHub app. To raise security fixes, the app needs read access to Dependabot alerts, which the repository owner checks under **Settings > GitHub Apps**.
+Renovate runs as a self-hosted job in CI, from `.github/workflows/update-dependencies.yml` on GitHub Actions. It needs the `RENOVATE_TOKEN` secret and the `RENOVATE_GIT_AUTHOR` variable, and skips its run with a `Skipping job.` message when either is missing. Its scheduled runs fall outside the schedule windows in `renovate.json`, so they open no pull requests for the update groups. Start a run by hand with the workflow's manual trigger, which ignores the schedules unless its `preserve_schedules` option is selected.
+
+On CircleCI, the job is in `.circleci/update-dependencies.yml` and also needs `RENOVATE_REPOSITORIES`. Start it by hand with the `run_update_dependencies` pipeline parameter set to `true`.
 
 ## Verify the update
 
