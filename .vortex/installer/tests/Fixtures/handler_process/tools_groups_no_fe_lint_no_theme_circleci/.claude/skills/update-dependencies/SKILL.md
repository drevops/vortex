@@ -87,32 +87,6 @@
 - Ignore an advisory only after the developer assessed it: add its ID under `config.policy.advisories.ignore-id` in `composer.json`, with the reason as the value, and repeat the reason in the commit message. The audit then lists it under `Found 1 ignored security vulnerability advisory`.
 - When the advisory needs a change in custom code, follow the `secure-code` skill.
 
-## Update the root npm packages
-
-The root `package.json` holds the linters and the test runner for the custom modules. Node.js and npm come from the `cli` container, so leave the `engines` versions alone.
-
-- See what is outdated: `ahoy cli "npm outdated"`. It exits with a non-zero code when any package is outdated.
-- Update within the ranges in `package.json`: `ahoy cli "npm update"`. It rewrites `package-lock.json`.
-- Move a package beyond its range: `ahoy cli "npm install --save-dev <package>@<version>"`
-- Audit: `ahoy cli "npm audit"`. `.npmrc` sets `audit-level=high`. CI applies the allowlist in `package-audit.jsonc` through `audit-ci`, which you run as `ahoy cli "npx --yes audit-ci --high --extra-args=--package-lock-only --config package-audit.jsonc"`.
-- Fix an advisory in an indirect dependency by pinning the fixed version in the `overrides` object of `package.json`, then run `ahoy cli "npm install"`. Add an `allowlist` entry to `package-audit.jsonc`, with `notes` and an `expiry` date, only when no fixed version exists and the developer assessed the advisory.
-- Confirm that the lock file matches `package.json`: `ahoy fei`. It runs `npm ci`, which fails on a mismatch.
-
-Commit `package.json` and `package-lock.json` together.
-
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
@@ -140,8 +114,6 @@
 - `ahoy test`
 
 - `ahoy test-bdd`, which needs the provisioned site
-
-- `ahoy test-js`
 
 Rector resolves its Drupal sets from the installed `drupal/core` version, so a core update can bring new findings. After updating the Rector packages, clear its cache: `ahoy cli vendor/bin/rector process --clear-cache --dry-run`
 
