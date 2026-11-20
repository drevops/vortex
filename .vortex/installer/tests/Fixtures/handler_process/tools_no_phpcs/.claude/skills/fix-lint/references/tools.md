@@ -2,26 +2,6 @@
 
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
 ## PHPStan
 
 `phpstan.neon` runs the analysis at level 7, with the Drupal rules from `mglaman/phpstan-drupal`, for PHP __VERSION__ (`phpVersion: __VERSION__`). It analyzes `web/modules/custom`, the custom theme, `web/sites/default/settings.php`, `web/sites/default/includes`, `tests` and `scripts`. Its `ignoreErrors` list skips missing iterable value types in the custom modules, the custom theme and the tests, and possibly undefined variables in the settings includes.
