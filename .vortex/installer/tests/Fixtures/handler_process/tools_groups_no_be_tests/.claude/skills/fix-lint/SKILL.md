@@ -37,8 +37,6 @@
 
 - Stylelint also runs on the custom theme, through the `lint` and `lint-fix` scripts in `${WEBROOT}/themes/custom/${DRUPAL_THEME}/package.json`, which `ahoy lint-fe` and `ahoy lint-fe-fix` call.
 
-- Gherkin Lint runs in `ahoy lint-tests`. It has no fixer, so `ahoy lint-fix` leaves feature files alone.
-
 - DCLint runs only in the CI `lint` job. The project doesn't install it.
 
 - Hadolint runs only in the CI `lint` job. The project doesn't install it.
