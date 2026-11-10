@@ -49,15 +49,6 @@
 ])
 ```
 
-## Twig CS Fixer
-
-Run `ahoy cli vendor/bin/twig-cs-fixer lint --debug <path>` to get the error identifier, such as `VariableName.Error`. The directives take no reason, so write the reason in a Twig comment on the line above the directive.
-
-- The next line: `{# twig-cs-fixer-disable-next-line VariableName.Error #}` on the line before it.
-- 1 line: `{# twig-cs-fixer-disable-line VariableName.Error #}` at the end of it.
-- A whole file: `{# twig-cs-fixer-disable VariableName.Error #}` in the file header. Without an identifier, the comment silences every rule, so always name one.
-- A directory: `$finder->exclude('<directory>');` in `.twig-cs-fixer.php`, with a comment above it that says why.
-
 ## ESLint
 
 ESLint reads the text after ` -- ` in a directive as its description, so the reason goes there.
