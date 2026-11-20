@@ -72,38 +72,10 @@
 
 Results are cached in `.twig-cs-fixer.cache`, which Git ignores. Add `--no-cache` when the results look stale.
 
-## ESLint
-
-The root `eslint.config.mjs` mirrors Drupal core's configuration: the recommended presets of `@eslint/js`, `eslint-plugin-import-x`, `eslint-plugin-jsdoc`, `eslint-plugin-prettier` and `eslint-plugin-yml`, and every `eslint-plugin-no-jquery` rule. `no-console`, `prettier/prettier`, `operator-linebreak` and `yml/indent` are errors. `.prettierrc.json` sets a `printWidth` of 160, single quotes, trailing commas everywhere, and 80 columns for documentation blocks. The root `lint-js` script checks the JavaScript and YAML in `web/modules/custom` with `--max-warnings=0`, so warnings such as `no-unused-vars` fail it too.
-
-`--fix` applies the Prettier formatting and the fixable rules. Fix the rest by hand:
-
-- `no-console`: remove the call. Console calls print to every visitor's browser.
-- `no-jquery/*`: replace the jQuery call with the DOM API, and attach behaviors with `once()`.
-- `no-unused-vars`: remove the variable or use it.
-- `jsdoc/*`: complete the documentation block.
-- `no-undef` for a library global: the configuration declares `Drupal`, `drupalSettings`, `once`, `jQuery` and other Drupal globals. Add a new global to `languageOptions.globals` only after the developer approves.
-
-Check or fix 1 file:
-
-```shell
-ahoy cli "npx eslint --max-warnings=0 web/modules/custom/<module>/js/<name>.js"
-ahoy cli "npx eslint --fix web/modules/custom/<module>/js/<name>.js"
-```
-
-The custom theme has its own `eslint.config.mjs` and `.prettierrc.json` with the same rules. Its `lint-js` script checks `js/**/*.js` without the minified files, and omits `--max-warnings=0`, so warnings don't fail it. Check or fix the theme's JavaScript:
-
-```shell
-ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-js"
-ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-js-fix"
-```
-
 ## Stylelint
 
 The root `.stylelintrc.js` extends `stylelint-config-standard` and adds `order/properties-alphabetical-order`. It allows the Sass at-rules such as `@include` and `@mixin`, but not `@use` or `@forward`, and it turns off the class, ID, custom property and keyframes name patterns. The root `lint-css` script checks `web/modules/custom/**/*.css`.
 
-The root `lint` script runs `lint-css` after `lint-js`, so a JavaScript failure hides the stylesheet results. Run Stylelint alone to see them.
-
 `--fix` sorts declarations alphabetically and fixes the other fixable rules. Fix the rest by hand, such as unknown properties, invalid values and duplicate selectors.
 
 Check or fix 1 file:
@@ -111,18 +83,6 @@
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
