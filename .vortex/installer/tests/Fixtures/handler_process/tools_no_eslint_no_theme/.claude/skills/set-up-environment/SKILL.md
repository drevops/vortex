@@ -58,8 +58,6 @@
 
 - `ahoy fei`: installs the npm packages of the root `package.json` with `npm ci`.
 
-- `ahoy fei` installs the theme's npm packages with `npm ci` in `web/themes/custom/<theme>`, and `ahoy fe` builds the theme assets.
-
 - `ahoy provision`: imports the database or installs the site, then runs database updates, the configuration import, cache rebuilds, deploy hooks, sanitization and the `scripts/provision-*.sh` scripts.
 - `ahoy info`: prints the project details and a one-time login link.
 
@@ -124,8 +122,6 @@
 - `composer.json` or `composer.lock` changed: `ahoy composer install`.
 
 - `package.json` or `package-lock.json` in the project root changed: `ahoy fei`.
-
-- The theme's `package.json` changed: `ahoy fei`. Any other file in `web/themes/custom/<theme>` changed: `ahoy fe`, or `ahoy fed` for a development build.
 
 - `docker-compose.yml` or a file in `.docker/` changed: `ahoy up --build`, which rebuilds the images and recreates the containers.
 - `.env` changed: `ahoy up`, or `ahoy restart` for a variable that only Drupal reads, as the overrides section above describes.
