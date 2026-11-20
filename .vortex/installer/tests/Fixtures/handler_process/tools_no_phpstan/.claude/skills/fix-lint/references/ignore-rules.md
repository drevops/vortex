@@ -18,24 +18,6 @@
 </rule>
 ```
 
-## PHPStan
-
-PHPStan ignores 1 line at a time and has no block ignore. Use the identifier that PHPStan prints with the error, and put the reason in parentheses after it.
-
-- The next line: `// @phpstan-ignore variable.undefined (<reason>)` on the line before it. At the end of a line, the same comment applies to that line. Don't use `@phpstan-ignore-next-line`, which silences every error on the line.
-- Some files or the whole project: an `ignoreErrors` entry in `phpstan.neon`, with a comment that says why, a `message` regular expression or an `identifier`, and `paths` to limit it, as the shipped entries have:
-
-```yaml
-parameters:
-  ignoreErrors:
-    - # <reason>.
-      identifier: missingType.iterableValue
-      paths:
-        - web/modules/custom/<module>/src/<File>.php
-```
-
-To leave a file out of the analysis, add it to `excludePaths`. The baseline, `phpstan-baseline.neon`, is for onboarding an existing codebase, not for ignoring a new error.
-
 ## Rector
 
 Rector has no inline ignore. Skip a rule for specific paths in `withSkip()` in `rector.php`, with a comment above it that says why. Skip a rule for the whole project only when the project's coding standards reject it, as the shipped list does.
