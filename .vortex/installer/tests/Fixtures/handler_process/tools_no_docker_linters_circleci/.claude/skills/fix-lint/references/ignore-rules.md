@@ -80,23 +80,3 @@
 ## Gherkin Lint
 
 Gherkin Lint has no line or block ignore. A `# @gherkinlint-disable-rule <rule>, <rule>` comment before the `Feature:` line turns the listed rules off for the whole file. Write the reason in a separate comment line above it. To turn a rule off for every file, set `enabled` to `false` for it in `gherkinlint.json`.
-
-## DCLint
-
-Put the rule name after `disable-line`, and the reason after a second `#`:
-
-- 1 line: `# dclint disable-line <rule> # <reason>` on its own line right before the line, or at the end of it.
-- A whole file: `# dclint disable <rule>` as the first line of the file. Without a rule name, the comment turns off every rule.
-- The whole project: set the rule to `0` in `.dclintrc`.
-
-## Hadolint
-
-Put the rule codes after `ignore=`, and the reason after a second `#`, as the Dockerfiles in `.docker/` do:
-
-```Dockerfile
-# hadolint ignore=DL3018 # the package set tracks the pinned base image
-RUN apk add --no-cache tzdata
-```
-
-- The next instruction: an `ignore=` directive on the line before it.
-- A whole file: `# hadolint global ignore=<codes>` in the file header, with the reason in a comment line above it.
