@@ -68,15 +68,6 @@
 - A whole file: `/* eslint-disable no-console -- <reason> */` in the file header. Never use `/* eslint-disable */` without a rule name.
 - Paths: the `ignores` list in `eslint.config.mjs`, with a comment that says why.
 
-## Stylelint
-
-Stylelint reads the text after ` -- ` in a directive as its description, so the reason goes there.
-
-- The next line: `/* stylelint-disable-next-line declaration-no-important -- <reason> */`
-- 1 line: `/* stylelint-disable-line declaration-no-important -- <reason> */` at the end of it.
-- A block: `/* stylelint-disable declaration-no-important -- <reason> */` before it and `/* stylelint-enable declaration-no-important */` after it.
-- Files: the `ignoreFiles` key of the configuration file. Never use `/* stylelint-disable */` without a rule name.
-
 ## Gherkin Lint
 
 Gherkin Lint has no line or block ignore. A `# @gherkinlint-disable-rule <rule>, <rule>` comment before the `Feature:` line turns the listed rules off for the whole file. Write the reason in a separate comment line above it. To turn a rule off for every file, set `enabled` to `false` for it in `gherkinlint.json`.
