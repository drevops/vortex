@@ -29,21 +29,11 @@
 
 - Twig CS Fixer runs first in `ahoy lint-fe`, and with `--fix` in `ahoy lint-fe-fix`.
 
-- ESLint runs in `ahoy lint-fe` through the `lint-js` script of the root `package.json`, and in `ahoy lint-fe-fix` through `lint-fix-js`.
-
-- ESLint also runs on the custom theme, through the `lint` and `lint-fix` scripts in `${WEBROOT}/themes/custom/${DRUPAL_THEME}/package.json`, which `ahoy lint-fe` and `ahoy lint-fe-fix` call.
-
-- Stylelint runs in `ahoy lint-fe` through the `lint-css` script of the root `package.json`, and in `ahoy lint-fe-fix` through `lint-fix-css`.
-
-- Stylelint also runs on the custom theme, through the `lint` and `lint-fix` scripts in `${WEBROOT}/themes/custom/${DRUPAL_THEME}/package.json`, which `ahoy lint-fe` and `ahoy lint-fe-fix` call.
-
 - Gherkin Lint runs in `ahoy lint-tests`. It has no fixer, so `ahoy lint-fix` leaves feature files alone.
 
 - DCLint runs only in the CI `lint` job. The project doesn't install it.
 
 - Hadolint runs only in the CI `lint` job. The project doesn't install it.
-
-`ahoy lint-sdc` validates the custom theme's components against a provisioned site. `ahoy lint` doesn't run it.
 
 Every Ahoy command runs its steps under `bash -e`, so it stops at the first tool that fails, and the tools after it report nothing until it passes. A fixer that leaves problems it can't fix also exits with a non-zero code, which stops `ahoy lint-fix` before the remaining fixers run. Run `ahoy lint-fix` again after fixing those problems by hand.
 
