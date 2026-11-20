@@ -29,14 +29,6 @@
 
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
