@@ -22,25 +22,6 @@
 ahoy cli vendor/bin/phpcbf web/modules/custom/<module>/src/<File>.php
 ```
 
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
 ## Rector
 
 `rector.php` applies the PHP sets for the `require.php` constraint in `composer.json`, the Drupal deprecation sets for the installed `drupal/core` version, and the `codeQuality`, `codingStyle`, `deadCode`, `privatization` and `typeDeclarations` sets. Its `withSkip()` list skips 12 rules everywhere and 2 more for specific paths, each based on the project's coding standards. It processes `web/modules/custom`, the custom theme, `web/sites/default/settings.php`, `web/sites/default/includes` and `tests`, but not `scripts`.
