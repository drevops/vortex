@@ -4,7 +4,7 @@
 
 ## PHPCS
 
-`phpcs.xml` applies the `Drupal`, `DrupalPractice`, `PHPCompatibility` and `DrevOps` standards, plus `Generic.PHP.RequireStrictTypes` and a Slevomat sniff that forbids spaces around `|` and `&` in type declarations. `PHPCompatibility` checks against the `testVersion` value, `8.4`. PHPCS checks `web/modules/custom`, the custom theme, `web/sites/default/settings.php`, `web/sites/default/includes`, `tests` and `scripts`. The `s` argument in the ruleset prints the sniff code after each message, and that code is the name to look up and to ignore. Warnings fail the check too: PHPCS exits with a non-zero code on any warning, and the ruleset reports 3 blank-line sniffs as warnings.
+`phpcs.xml` applies the `Drupal`, `DrupalPractice`, `PHPCompatibility` and `DrevOps` standards, plus `Generic.PHP.RequireStrictTypes` and a Slevomat sniff that forbids spaces around `|` and `&` in type declarations. `PHPCompatibility` checks against the `testVersion` value, `8.4`. PHPCS checks `docroot/modules/custom`, the custom theme, `docroot/sites/default/settings.php`, `docroot/sites/default/includes`, `tests` and `scripts`. The `s` argument in the ruleset prints the sniff code after each message, and that code is the name to look up and to ignore. Warnings fail the check too: PHPCS exits with a non-zero code on any warning, and the ruleset reports 3 blank-line sniffs as warnings.
 
 PHPCBF fixes the violations that the report marks with `[x]`: spacing, indentation, and array and comment formatting. It also renames local variables and parameters to snake_case for the `DrevOps.NamingConventions` sniffs, so search for callers that pass a renamed parameter by name.
 
@@ -18,13 +18,13 @@
 Check or fix 1 file. A path on the command line replaces the `<file>` list in `phpcs.xml`:
 
 ```shell
-ahoy cli vendor/bin/phpcs web/modules/custom/<module>/src/<File>.php
-ahoy cli vendor/bin/phpcbf web/modules/custom/<module>/src/<File>.php
+ahoy cli vendor/bin/phpcs docroot/modules/custom/<module>/src/<File>.php
+ahoy cli vendor/bin/phpcbf docroot/modules/custom/<module>/src/<File>.php
 ```
 
 ## PHPStan
 
-`phpstan.neon` runs the analysis at level 7, with the Drupal rules from `mglaman/phpstan-drupal`, for PHP __VERSION__ (`phpVersion: __VERSION__`). It analyzes `web/modules/custom`, the custom theme, `web/sites/default/settings.php`, `web/sites/default/includes`, `tests` and `scripts`. Its `ignoreErrors` list skips missing iterable value types in the custom modules, the custom theme and the tests, and possibly undefined variables in the settings includes.
+`phpstan.neon` runs the analysis at level 7, with the Drupal rules from `mglaman/phpstan-drupal`, for PHP __VERSION__ (`phpVersion: __VERSION__`). It analyzes `docroot/modules/custom`, the custom theme, `docroot/sites/default/settings.php`, `docroot/sites/default/includes`, `tests` and `scripts`. Its `ignoreErrors` list skips missing iterable value types in the custom modules, the custom theme and the tests, and possibly undefined variables in the settings includes.
 
 PHPStan has no fixer. Fix each error by hand, using the identifier that PHPStan prints with the error:
 
@@ -38,12 +38,12 @@
 Analyze 1 file. A path on the command line replaces the `paths` list:
 
 ```shell
-ahoy cli vendor/bin/phpstan analyse web/modules/custom/<module>/src/<File>.php
+ahoy cli vendor/bin/phpstan analyse docroot/modules/custom/<module>/src/<File>.php
 ```
 
 ## Rector
 
-`rector.php` applies the PHP sets for the `require.php` constraint in `composer.json`, the Drupal deprecation sets for the installed `drupal/core` version, and the `codeQuality`, `codingStyle`, `deadCode`, `privatization` and `typeDeclarations` sets. Its `withSkip()` list skips 12 rules everywhere and 2 more for specific paths, each based on the project's coding standards. It processes `web/modules/custom`, the custom theme, `web/sites/default/settings.php`, `web/sites/default/includes` and `tests`, but not `scripts`.
+`rector.php` applies the PHP sets for the `require.php` constraint in `composer.json`, the Drupal deprecation sets for the installed `drupal/core` version, and the `codeQuality`, `codingStyle`, `deadCode`, `privatization` and `typeDeclarations` sets. Its `withSkip()` list skips 12 rules everywhere and 2 more for specific paths, each based on the project's coding standards. It processes `docroot/modules/custom`, the custom theme, `docroot/sites/default/settings.php`, `docroot/sites/default/includes` and `tests`, but not `scripts`.
 
 Rector is its own fixer. A failed check means that Rector would change the code: it prints a diff for each file and exits with code `2`. Apply the changes, then read them like any other refactoring, because the `deadCode` and `typeDeclarations` sets change code, not only its formatting. Run the tests that cover the changed files. When a change is wrong for this project, skip that rule for that path in `withSkip()`, after the developer approves.
 
@@ -50,8 +50,8 @@
 Check or fix 1 file:
 
 ```shell
-ahoy cli vendor/bin/rector process web/modules/custom/<module>/src/<File>.php --dry-run
-ahoy cli vendor/bin/rector process web/modules/custom/<module>/src/<File>.php
+ahoy cli vendor/bin/rector process docroot/modules/custom/<module>/src/<File>.php --dry-run
+ahoy cli vendor/bin/rector process docroot/modules/custom/<module>/src/<File>.php
 ```
 
 Rector caches its results. After changing `rector.php`, updating the Rector packages or switching to a branch with a different configuration, clear the cache: `ahoy cli vendor/bin/rector process --clear-cache --dry-run`
@@ -58,7 +58,7 @@
 
 ## Twig CS Fixer
 
-`.twig-cs-fixer.php` applies the `TwigCsFixer` standard with a 2-space indent to the Twig templates in `web/modules/custom` and the custom theme. `allowNonFixableRules()` turns on the rules that have no automatic fix, such as snake_case variable names. The configuration registers Drupal's `trans` tag parser, so `{% trans %}` blocks parse.
+`.twig-cs-fixer.php` applies the `TwigCsFixer` standard with a 2-space indent to the Twig templates in `docroot/modules/custom` and the custom theme. `allowNonFixableRules()` turns on the rules that have no automatic fix, such as snake_case variable names. The configuration registers Drupal's `trans` tag parser, so `{% trans %}` blocks parse.
 
 `lint --fix` fixes indentation, whitespace, spacing around delimiters and operators, quotes and trailing commas. Fix the non-fixable rules by hand. For a variable name, rename it where the template sets it with `{% set %}`, or in the theme hook's variables and the preprocess function that fills them.
 
@@ -65,9 +65,9 @@
 Check or fix 1 template. A path on the command line replaces the directories in the configuration. `--debug` prints each error's identifier, which an ignore needs:
 
 ```shell
-ahoy cli vendor/bin/twig-cs-fixer lint web/modules/custom/<module>/templates/<name>.html.twig
-ahoy cli vendor/bin/twig-cs-fixer lint --fix web/modules/custom/<module>/templates/<name>.html.twig
-ahoy cli vendor/bin/twig-cs-fixer lint --debug web/modules/custom/<module>/templates/<name>.html.twig
+ahoy cli vendor/bin/twig-cs-fixer lint docroot/modules/custom/<module>/templates/<name>.html.twig
+ahoy cli vendor/bin/twig-cs-fixer lint --fix docroot/modules/custom/<module>/templates/<name>.html.twig
+ahoy cli vendor/bin/twig-cs-fixer lint --debug docroot/modules/custom/<module>/templates/<name>.html.twig
 ```
 
 Results are cached in `.twig-cs-fixer.cache`, which Git ignores. Add `--no-cache` when the results look stale.
@@ -74,7 +74,7 @@
 
 ## ESLint
 
-The root `eslint.config.mjs` mirrors Drupal core's configuration: the recommended presets of `@eslint/js`, `eslint-plugin-import-x`, `eslint-plugin-jsdoc`, `eslint-plugin-prettier` and `eslint-plugin-yml`, and every `eslint-plugin-no-jquery` rule. `no-console`, `prettier/prettier`, `operator-linebreak` and `yml/indent` are errors. `.prettierrc.json` sets a `printWidth` of 160, single quotes, trailing commas everywhere, and 80 columns for documentation blocks. The root `lint-js` script checks the JavaScript and YAML in `web/modules/custom` with `--max-warnings=0`, so warnings such as `no-unused-vars` fail it too.
+The root `eslint.config.mjs` mirrors Drupal core's configuration: the recommended presets of `@eslint/js`, `eslint-plugin-import-x`, `eslint-plugin-jsdoc`, `eslint-plugin-prettier` and `eslint-plugin-yml`, and every `eslint-plugin-no-jquery` rule. `no-console`, `prettier/prettier`, `operator-linebreak` and `yml/indent` are errors. `.prettierrc.json` sets a `printWidth` of 160, single quotes, trailing commas everywhere, and 80 columns for documentation blocks. The root `lint-js` script checks the JavaScript and YAML in `docroot/modules/custom` with `--max-warnings=0`, so warnings such as `no-unused-vars` fail it too.
 
 `--fix` applies the Prettier formatting and the fixable rules. Fix the rest by hand:
 
@@ -87,8 +87,8 @@
 Check or fix 1 file:
 
 ```shell
-ahoy cli "npx eslint --max-warnings=0 web/modules/custom/<module>/js/<name>.js"
-ahoy cli "npx eslint --fix web/modules/custom/<module>/js/<name>.js"
+ahoy cli "npx eslint --max-warnings=0 docroot/modules/custom/<module>/js/<name>.js"
+ahoy cli "npx eslint --fix docroot/modules/custom/<module>/js/<name>.js"
 ```
 
 The custom theme has its own `eslint.config.mjs` and `.prettierrc.json` with the same rules. Its `lint-js` script checks `js/**/*.js` without the minified files, and omits `--max-warnings=0`, so warnings don't fail it. Check or fix the theme's JavaScript:
@@ -100,7 +100,7 @@
 
 ## Stylelint
 
-The root `.stylelintrc.js` extends `stylelint-config-standard` and adds `order/properties-alphabetical-order`. It allows the Sass at-rules such as `@include` and `@mixin`, but not `@use` or `@forward`, and it turns off the class, ID, custom property and keyframes name patterns. The root `lint-css` script checks `web/modules/custom/**/*.css`.
+The root `.stylelintrc.js` extends `stylelint-config-standard` and adds `order/properties-alphabetical-order`. It allows the Sass at-rules such as `@include` and `@mixin`, but not `@use` or `@forward`, and it turns off the class, ID, custom property and keyframes name patterns. The root `lint-css` script checks `docroot/modules/custom/**/*.css`.
 
 The root `lint` script runs `lint-css` after `lint-js`, so a JavaScript failure hides the stylesheet results. Run Stylelint alone to see them.
 
@@ -109,8 +109,8 @@
 Check or fix 1 file:
 
 ```shell
-ahoy cli "npx stylelint web/modules/custom/<module>/css/<name>.css"
-ahoy cli "npx stylelint --fix web/modules/custom/<module>/css/<name>.css"
+ahoy cli "npx stylelint docroot/modules/custom/<module>/css/<name>.css"
+ahoy cli "npx stylelint --fix docroot/modules/custom/<module>/css/<name>.css"
 ```
 
 The custom theme has its own `.stylelintrc.json`. It extends `stylelint-config-standard` and `stylelint-config-standard-scss`, skips the property order rule, and adds 2 rules that need a manual fix:
