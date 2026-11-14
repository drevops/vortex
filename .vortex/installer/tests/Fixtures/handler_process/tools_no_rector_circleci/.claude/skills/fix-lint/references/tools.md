@@ -41,21 +41,6 @@
 ahoy cli vendor/bin/phpstan analyse web/modules/custom/<module>/src/<File>.php
 ```
 
-## Rector
-
-`rector.php` applies the PHP sets for the `require.php` constraint in `composer.json`, the Drupal deprecation sets for the installed `drupal/core` version, and the `codeQuality`, `codingStyle`, `deadCode`, `privatization` and `typeDeclarations` sets. Its `withSkip()` list skips 12 rules everywhere and 2 more for specific paths, each based on the project's coding standards. It processes `web/modules/custom`, the custom theme, `web/sites/default/settings.php`, `web/sites/default/includes` and `tests`, but not `scripts`.
-
-Rector is its own fixer. A failed check means that Rector would change the code: it prints a diff for each file and exits with code `2`. Apply the changes, then read them like any other refactoring, because the `deadCode` and `typeDeclarations` sets change code, not only its formatting. Run the tests that cover the changed files. When a change is wrong for this project, skip that rule for that path in `withSkip()`, after the developer approves.
-
-Check or fix 1 file:
-
-```shell
-ahoy cli vendor/bin/rector process web/modules/custom/<module>/src/<File>.php --dry-run
-ahoy cli vendor/bin/rector process web/modules/custom/<module>/src/<File>.php
-```
-
-Rector caches its results. After changing `rector.php`, updating the Rector packages or switching to a branch with a different configuration, clear the cache: `ahoy cli vendor/bin/rector process --clear-cache --dry-run`
-
 ## Twig CS Fixer
 
 `.twig-cs-fixer.php` applies the `TwigCsFixer` standard with a 2-space indent to the Twig templates in `web/modules/custom` and the custom theme. `allowNonFixableRules()` turns on the rules that have no automatic fix, such as snake_case variable names. The configuration registers Drupal's `trans` tag parser, so `{% trans %}` blocks parse.
