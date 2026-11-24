@@ -58,16 +58,6 @@
 - A whole file: `{# twig-cs-fixer-disable VariableName.Error #}` in the file header. Without an identifier, the comment silences every rule, so always name one.
 - A directory: `$finder->exclude('<directory>');` in `.twig-cs-fixer.php`, with a comment above it that says why.
 
-## ESLint
-
-ESLint reads the text after ` -- ` in a directive as its description, so the reason goes there.
-
-- The next line: `// eslint-disable-next-line no-console -- <reason>`
-- 1 line: `// eslint-disable-line no-console -- <reason>` at the end of it.
-- A block: `/* eslint-disable no-console -- <reason> */` before it and `/* eslint-enable no-console */` after it.
-- A whole file: `/* eslint-disable no-console -- <reason> */` in the file header. Never use `/* eslint-disable */` without a rule name.
-- Paths: the `ignores` list in `eslint.config.mjs`, with a comment that says why.
-
 ## Stylelint
 
 Stylelint reads the text after ` -- ` in a directive as its description, so the reason goes there.
