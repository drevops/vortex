@@ -98,33 +98,6 @@
 ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-js-fix"
 ```
 
-## Stylelint
-
-The root `.stylelintrc.js` extends `stylelint-config-standard` and adds `order/properties-alphabetical-order`. It allows the Sass at-rules such as `@include` and `@mixin`, but not `@use` or `@forward`, and it turns off the class, ID, custom property and keyframes name patterns. The root `lint-css` script checks `web/modules/custom/**/*.css`.
-
-The root `lint` script runs `lint-css` after `lint-js`, so a JavaScript failure hides the stylesheet results. Run Stylelint alone to see them.
-
-`--fix` sorts declarations alphabetically and fixes the other fixable rules. Fix the rest by hand, such as unknown properties, invalid values and duplicate selectors.
-
-Check or fix 1 file:
-
-```shell
-ahoy cli "npx stylelint web/modules/custom/<module>/css/<name>.css"
-ahoy cli "npx stylelint --fix web/modules/custom/<module>/css/<name>.css"
-```
-
-The custom theme has its own `.stylelintrc.json`. It extends `stylelint-config-standard` and `stylelint-config-standard-scss`, skips the property order rule, and adds 2 rules that need a manual fix:
-
-- `scss/at-extend-no-missing-placeholder`: turn the extended selector into a `%placeholder`.
-- `scss/dollar-variable-pattern`: rename the variable to lowercase words separated by hyphens, such as `$base-font-size`.
-
-Its scripts are `lint-css` and `lint-css-fix`, named unlike the root `lint-fix-css`. They check `scss/**/*.scss`:
-
-```shell
-ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-css"
-ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-css-fix"
-```
-
 ## Gherkin Lint
 
 `ahoy lint-tests` runs `vendor/bin/gherkinlint lint tests/behat/features`. `gherkinlint.json` sets the indentation to 2 spaces per level and puts doc strings at level 3. It turns off `keyword-order`, `no-homogenous-tags` and `scenario-size`, and every other rule stays on. List the rules with `ahoy cli vendor/bin/gherkinlint rules`.
