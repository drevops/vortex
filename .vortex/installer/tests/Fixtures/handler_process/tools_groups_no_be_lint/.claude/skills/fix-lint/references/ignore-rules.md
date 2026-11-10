@@ -2,53 +2,6 @@
 
 Each section shows how 1 tool ignores a rule, from the narrowest scope to the widest, and where the reason goes. Ask the developer before adding any of them, and name every ignore you added in the report.
 
-## PHPCS
-
-Use the sniff code that the report prints after the message. PHPCS reads the text after ` -- ` in an annotation as a note, so the reason goes there.
-
-- The next line: `// phpcs:ignore Drupal.Commenting.FunctionComment.Missing -- <reason>` on the line before it. At the end of a line, the same comment applies to that line.
-- A block: `// phpcs:disable <sniff code> -- <reason>` before it and `// phpcs:enable <sniff code>` after it.
-- A whole file: `// phpcs:disable <sniff code> -- <reason>` in the file header. Never use `// phpcs:ignoreFile`, which silences every sniff.
-- Some files or the whole project: a `<rule ref="...">` entry in `phpcs.xml` with `<exclude-pattern>` lines, and an XML comment above it that says why, as the shipped entries have:
-
-```xml
-<!-- <reason>. -->
-<rule ref="DrupalPractice.General.ClassName.ClassPrefix">
-    <exclude-pattern>*\/path\/to\/dir\/*\.php</exclude-pattern>
-</rule>
-```
-
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
