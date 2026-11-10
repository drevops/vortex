@@ -100,19 +100,6 @@
 
 Commit `package.json` and `package-lock.json` together.
 
-## Update the custom theme's npm packages
-
-The custom theme has its own `package.json`, `package-lock.json`, `.npmrc` and `package-audit.jsonc` in `${WEBROOT}/themes/custom/${DRUPAL_THEME}`. Run the npm commands for it with `--prefix`:
-
-- `ahoy cli "npm outdated --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`
-- `ahoy cli "npm update --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`
-- `ahoy cli "npm install --save-dev <package>@<version> --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`
-- `ahoy cli "npm audit --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`
-
-The theme's `postinstall` script runs `patch-package`, which applies the theme's npm patches from its own `patches/` directory. It exits with code `0` outside CI even when a patch fails, so read the output of `ahoy fei` for patch errors. To re-create a patch, edit the package's files under the theme's `node_modules/`, then run `ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} postinstall -- <package>"`.
-
-After the update, run `ahoy fei` to install from the lock files, and `ahoy fe` to rebuild the theme's assets.
-
 ## Automated update pull requests
 
 Renovate opens update pull requests from branches with the `deps/` prefix, labeled `Dependencies`. Security fixes also carry the `Security` label. Read [references/renovate.md](references/renovate.md) for the groups, schedules and delays in `renovate.json`, and for what Renovate leaves to be updated by hand.
