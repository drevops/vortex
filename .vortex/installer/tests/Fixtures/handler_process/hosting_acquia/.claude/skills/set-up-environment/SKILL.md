@@ -52,13 +52,13 @@
 
 Then ask the developer, and run `AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy build` in the background. It runs these steps in order and stops at the first one that fails:
 
-- `ahoy reset`: runs `ahoy down`, then deletes `vendor`, every `node_modules` directory, `web/core` and the `contrib` directories.
+- `ahoy reset`: runs `ahoy down`, then deletes `vendor`, every `node_modules` directory, `docroot/core` and the `contrib` directories.
 - `ahoy up --build --force-recreate`: builds the images and starts the stack.
 - `ahoy composer install`: installs the Composer dependencies.
 
 - `ahoy fei`: installs the npm packages of the root `package.json` with `npm ci`.
 
-- `ahoy fei` installs the theme's npm packages with `npm ci` in `web/themes/custom/<theme>`, and `ahoy fe` builds the theme assets.
+- `ahoy fei` installs the theme's npm packages with `npm ci` in `docroot/themes/custom/<theme>`, and `ahoy fe` builds the theme assets.
 
 - `ahoy provision`: imports the database or installs the site, then runs database updates, the configuration import, cache rebuilds, deploy hooks, sanitization and the `scripts/provision-*.sh` scripts.
 - `ahoy info`: prints the project details and a one-time login link.
@@ -125,7 +125,7 @@
 
 - `package.json` or `package-lock.json` in the project root changed: `ahoy fei`.
 
-- The theme's `package.json` changed: `ahoy fei`. Any other file in `web/themes/custom/<theme>` changed: `ahoy fe`, or `ahoy fed` for a development build.
+- The theme's `package.json` changed: `ahoy fei`. Any other file in `docroot/themes/custom/<theme>` changed: `ahoy fe`, or `ahoy fed` for a development build.
 
 - `docker-compose.yml` or a file in `.docker/` changed: `ahoy up --build`, which rebuilds the images and recreates the containers.
 - `.env` changed: `ahoy up`, or `ahoy restart` for a variable that only Drupal reads, as the overrides section above describes.
