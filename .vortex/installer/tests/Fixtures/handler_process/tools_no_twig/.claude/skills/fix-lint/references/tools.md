@@ -56,22 +56,6 @@
 
 Rector caches its results. After changing `rector.php`, updating the Rector packages or switching to a branch with a different configuration, clear the cache: `ahoy cli vendor/bin/rector process --clear-cache --dry-run`
 
-## Twig CS Fixer
-
-`.twig-cs-fixer.php` applies the `TwigCsFixer` standard with a 2-space indent to the Twig templates in `web/modules/custom` and the custom theme. `allowNonFixableRules()` turns on the rules that have no automatic fix, such as snake_case variable names. The configuration registers Drupal's `trans` tag parser, so `{% trans %}` blocks parse.
-
-`lint --fix` fixes indentation, whitespace, spacing around delimiters and operators, quotes and trailing commas. Fix the non-fixable rules by hand. For a variable name, rename it where the template sets it with `{% set %}`, or in the theme hook's variables and the preprocess function that fills them.
-
-Check or fix 1 template. A path on the command line replaces the directories in the configuration. `--debug` prints each error's identifier, which an ignore needs:
-
-```shell
-ahoy cli vendor/bin/twig-cs-fixer lint web/modules/custom/<module>/templates/<name>.html.twig
-ahoy cli vendor/bin/twig-cs-fixer lint --fix web/modules/custom/<module>/templates/<name>.html.twig
-ahoy cli vendor/bin/twig-cs-fixer lint --debug web/modules/custom/<module>/templates/<name>.html.twig
-```
-
-Results are cached in `.twig-cs-fixer.cache`, which Git ignores. Add `--no-cache` when the results look stale.
-
 ## ESLint
 
 The root `eslint.config.mjs` mirrors Drupal core's configuration: the recommended presets of `@eslint/js`, `eslint-plugin-import-x`, `eslint-plugin-jsdoc`, `eslint-plugin-prettier` and `eslint-plugin-yml`, and every `eslint-plugin-no-jquery` rule. `no-console`, `prettier/prettier`, `operator-linebreak` and `yml/indent` are errors. `.prettierrc.json` sets a `printWidth` of 160, single quotes, trailing commas everywhere, and 80 columns for documentation blocks. The root `lint-js` script checks the JavaScript and YAML in `web/modules/custom` with `--max-warnings=0`, so warnings such as `no-unused-vars` fail it too.
