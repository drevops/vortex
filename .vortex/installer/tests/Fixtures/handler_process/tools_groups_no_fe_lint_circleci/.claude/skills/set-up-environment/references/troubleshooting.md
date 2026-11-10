@@ -87,8 +87,6 @@
 2. When Composer hits the GitHub API rate limit or can't reach a private repository, set `PACKAGE_TOKEN` in `.env.local` to a GitHub token. `ahoy composer` registers it for every run.
 3. When the new code ships update hooks, apply them with `ahoy provision`, or with `ahoy drush updatedb` for the updates alone.
 
-When the root `package.json` or `package-lock.json` changed, run `ahoy fei`. It runs `npm ci`, which installs exactly what the lock file lists and fails when the lock file and `package.json` disagree. A failure there means the lock file in the commit is out of date, so tell the developer instead of regenerating it.
-
 When files in `web/themes/custom/<theme>` changed, run `ahoy fei` for its npm packages and then `ahoy fe` to rebuild its assets. Unstyled pages after a pull usually mean the assets weren't rebuilt.
 
 ## A variable doesn't apply
