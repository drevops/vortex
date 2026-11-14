@@ -91,13 +91,6 @@
 ahoy cli "npx eslint --fix web/modules/custom/<module>/js/<name>.js"
 ```
 
-The custom theme has its own `eslint.config.mjs` and `.prettierrc.json` with the same rules. Its `lint-js` script checks `js/**/*.js` without the minified files, and omits `--max-warnings=0`, so warnings don't fail it. Check or fix the theme's JavaScript:
-
-```shell
-ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-js"
-ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-js-fix"
-```
-
 ## Stylelint
 
 The root `.stylelintrc.js` extends `stylelint-config-standard` and adds `order/properties-alphabetical-order`. It allows the Sass at-rules such as `@include` and `@mixin`, but not `@use` or `@forward`, and it turns off the class, ID, custom property and keyframes name patterns. The root `lint-css` script checks `web/modules/custom/**/*.css`.
@@ -111,18 +104,6 @@
 ```shell
 ahoy cli "npx stylelint web/modules/custom/<module>/css/<name>.css"
 ahoy cli "npx stylelint --fix web/modules/custom/<module>/css/<name>.css"
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
 ```
 
 ## Gherkin Lint
