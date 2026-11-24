@@ -2,60 +2,6 @@
 
 Each section covers 1 tool: what its configuration checks, what its fixer can and can't fix, how to fix the rest by hand, and how to run it alone on 1 file. Run every command from the project root, 1 command per Bash call.
 
-## PHPCS
-
-`phpcs.xml` applies the `Drupal`, `DrupalPractice`, `PHPCompatibility` and `DrevOps` standards, plus `Generic.PHP.RequireStrictTypes` and a Slevomat sniff that forbids spaces around `|` and `&` in type declarations. `PHPCompatibility` checks against the `testVersion` value, `8.4`. PHPCS checks `web/modules/custom`, the custom theme, `web/sites/default/settings.php`, `web/sites/default/includes`, `tests` and `scripts`. The `s` argument in the ruleset prints the sniff code after each message, and that code is the name to look up and to ignore. Warnings fail the check too: PHPCS exits with a non-zero code on any warning, and the ruleset reports 3 blank-line sniffs as warnings.
-
-PHPCBF fixes the violations that the report marks with `[x]`: spacing, indentation, and array and comment formatting. It also renames local variables and parameters to snake_case for the `DrevOps.NamingConventions` sniffs, so search for callers that pass a renamed parameter by name.
-
-Fix the rest by hand:
-
-- `Drupal.Commenting.*`: write the missing docblock in the Drupal style, with a 1-line summary and `@param` and `@return` tags.
-- `Generic.PHP.RequireStrictTypes.MissingDeclaration`: add `declare(strict_types=1);` after the file docblock, as the custom modules do.
-- `DrupalPractice.Objects.GlobalDrupal`: inject the service through the constructor instead of calling `\Drupal::` inside a class.
-- `DrevOps.TestingPractices.*`: name each data provider `dataProvider` followed by the test name without its `test` prefix, and place it right after its test.
-
-Check or fix 1 file. A path on the command line replaces the `<file>` list in `phpcs.xml`:
-
-```shell
-ahoy cli vendor/bin/phpcs web/modules/custom/<module>/src/<File>.php
-ahoy cli vendor/bin/phpcbf web/modules/custom/<module>/src/<File>.php
-```
-
-## PHPStan
-
-`phpstan.neon` runs the analysis at level 7, with the Drupal rules from `mglaman/phpstan-drupal`, for PHP __VERSION__ (`phpVersion: __VERSION__`). It analyzes `web/modules/custom`, the custom theme, `web/sites/default/settings.php`, `web/sites/default/includes`, `tests` and `scripts`. Its `ignoreErrors` list skips missing iterable value types in the custom modules, the custom theme and the tests, and possibly undefined variables in the settings includes.
-
-PHPStan has no fixer. Fix each error by hand, using the identifier that PHPStan prints with the error:
-
-- `missingType.iterableValue`: declare the value type in the docblock, such as `@param array<string, mixed> $options` or `@return string[]`.
-- Errors about `mixed` or a nullable value: narrow the value with `instanceof`, `is_string()` or a native type declaration. `treatPhpDocTypesAsCertain` is `false`, so a runtime check stays valid when only a docblock declares the type.
-- `\Drupal calls should be avoided in classes, use dependency injection instead`: inject the service through the constructor.
-- `Ignored error pattern ... was not matched in reported errors`: the code no longer has that error, so remove the stale entry from `ignoreErrors`, or from the baseline as the next paragraph describes.
-
-`ahoy lint-be-baseline` records the current errors in `phpstan-baseline.neon`. Use it only to onboard an existing codebase, and only when the developer asks for it, never to hide a new error. After fixing errors that a baseline records, run it again to shrink the baseline. See [Baseline for an existing codebase](https://www.vortextemplate.com/docs/development/code-quality/phpstan#baseline-for-an-existing-codebase).
-
-Analyze 1 file. A path on the command line replaces the `paths` list:
-
-```shell
-ahoy cli vendor/bin/phpstan analyse web/modules/custom/<module>/src/<File>.php
-```
-
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
