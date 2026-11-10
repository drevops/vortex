@@ -56,8 +56,6 @@
 - `ahoy up --build --force-recreate`: builds the images and starts the stack.
 - `ahoy composer install`: installs the Composer dependencies.
 
-- `ahoy fei`: installs the npm packages of the root `package.json` with `npm ci`.
-
 - `ahoy fei` installs the theme's npm packages with `npm ci` in `web/themes/custom/<theme>`, and `ahoy fe` builds the theme assets.
 
 - `ahoy provision`: imports the database or installs the site, then runs database updates, the configuration import, cache rebuilds, deploy hooks, sanitization and the `scripts/provision-*.sh` scripts.
@@ -122,8 +120,6 @@
 Skip the full rebuild. Read what changed with `git diff --stat ORIG_HEAD HEAD` or the branch diff, and run only the matching commands:
 
 - `composer.json` or `composer.lock` changed: `ahoy composer install`.
-
-- `package.json` or `package-lock.json` in the project root changed: `ahoy fei`.
 
 - The theme's `package.json` changed: `ahoy fei`. Any other file in `web/themes/custom/<theme>` changed: `ahoy fe`, or `ahoy fed` for a development build.
 
