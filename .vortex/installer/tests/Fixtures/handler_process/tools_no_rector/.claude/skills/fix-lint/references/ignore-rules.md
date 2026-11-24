@@ -36,19 +36,6 @@
 
 To leave a file out of the analysis, add it to `excludePaths`. The baseline, `phpstan-baseline.neon`, is for onboarding an existing codebase, not for ignoring a new error.
 
-## Rector
-
-Rector has no inline ignore. Skip a rule for specific paths in `withSkip()` in `rector.php`, with a comment above it that says why. Skip a rule for the whole project only when the project's coding standards reject it, as the shipped list does.
-
-```php
-->withSkip([
-  // <reason>.
-  SimplifyIfReturnBoolRector::class => [
-    __DIR__ . '/web/modules/custom/<module>/src/<File>.php',
-  ],
-])
-```
-
 ## Twig CS Fixer
 
 Run `ahoy cli vendor/bin/twig-cs-fixer lint --debug <path>` to get the error identifier, such as `VariableName.Error`. The directives take no reason, so write the reason in a Twig comment on the line above the directive.
