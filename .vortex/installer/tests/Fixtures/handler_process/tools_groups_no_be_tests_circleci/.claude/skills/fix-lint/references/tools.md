@@ -125,21 +125,6 @@
 ahoy cli "npm run --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint-css-fix"
 ```
 
-## Gherkin Lint
-
-`ahoy lint-tests` runs `vendor/bin/gherkinlint lint tests/behat/features`. `gherkinlint.json` sets the indentation to 2 spaces per level and puts doc strings at level 3. It turns off `keyword-order`, `no-homogenous-tags` and `scenario-size`, and every other rule stays on. List the rules with `ahoy cli vendor/bin/gherkinlint rules`.
-
-There is no fixer. Fix the feature file by hand:
-
-- Indentation: `Feature:` at 0 spaces, `Background:`, `Scenario:`, `Scenario Outline:` and `Rule:` at 2, steps and `Examples:` at 4, and table rows and the opening `"""` of a doc string at 6.
-- Duplicate feature or scenario names, empty scenarios, duplicate tags and trailing spaces: rename, fill or remove them.
-
-Check 1 file:
-
-```shell
-ahoy cli vendor/bin/gherkinlint lint tests/behat/features/<name>.feature
-```
-
 ## DCLint
 
 The CI `lint` job runs the `zavoloklom/dclint` image on the project root, with the configuration in `.dclintrc`. Errors fail the job. Warnings, such as service keys out of order or unquoted ports, appear in the log without failing it. `.dclintrc` turns off 4 rules and sets `service-keys-order` to a warning with 8 groups of keys. Before linting, CI deletes the lines of `docker-compose.yml` that contain `###` and removes the `##` comment markers, so it checks the CI variant of the file.
