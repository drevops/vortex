@@ -77,10 +77,6 @@
 - A block: `/* stylelint-disable declaration-no-important -- <reason> */` before it and `/* stylelint-enable declaration-no-important */` after it.
 - Files: the `ignoreFiles` key of the configuration file. Never use `/* stylelint-disable */` without a rule name.
 
-## Gherkin Lint
-
-Gherkin Lint has no line or block ignore. A `# @gherkinlint-disable-rule <rule>, <rule>` comment before the `Feature:` line turns the listed rules off for the whole file. Write the reason in a separate comment line above it. To turn a rule off for every file, set `enabled` to `false` for it in `gherkinlint.json`.
-
 ## DCLint
 
 Put the rule name after `disable-line`, and the reason after a second `#`:
