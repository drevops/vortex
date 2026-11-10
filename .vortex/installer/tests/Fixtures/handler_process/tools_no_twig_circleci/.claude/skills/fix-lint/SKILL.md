@@ -27,8 +27,6 @@
 
 - Rector runs last in `ahoy lint-be`, as a dry run. `ahoy lint-be-fix` runs it first and applies its changes.
 
-- Twig CS Fixer runs first in `ahoy lint-fe`, and with `--fix` in `ahoy lint-fe-fix`.
-
 - ESLint runs in `ahoy lint-fe` through the `lint-js` script of the root `package.json`, and in `ahoy lint-fe-fix` through `lint-fix-js`.
 
 - ESLint also runs on the custom theme, through the `lint` and `lint-fix` scripts in `${WEBROOT}/themes/custom/${DRUPAL_THEME}/package.json`, which `ahoy lint-fe` and `ahoy lint-fe-fix` call.
