@@ -51,8 +51,6 @@
 
 A kernel test installs only the modules in `$modules`. A functional test installs a whole site for every test method, so it is the slowest PHPUnit type by far.
 
-The last step is a Behat scenario: a user journey across pages, run against the provisioned site. Spend scenarios on the journeys that matter most, such as logging in, editing content or submitting a form, and keep edge cases in cheaper tests.
-
 ## Avoid tests that pass without proving anything
 
 - **Assertions over an empty result.** A check that "no item has X" or "every item has Y" passes when there are no items. Assert the count, or the presence of a known item, before checking each one.
@@ -162,46 +160,6 @@
 ### Fixtures
 
 Kernel and functional tests install a throwaway site in the `SIMPLETEST_DB` database under a table prefix. Nothing they create reaches the local site, and nothing from the local site exists in them: create content types, fields, users and content in `setUp()` or in the test. In a kernel test, list every module the code needs in `$modules`, and install the entity schemas and configuration it reads with `installEntitySchema()` and `installConfig()`.
-
-## Behat scenarios
-
-### Write them
-
-- Put each feature in `tests/behat/features/<name>.feature`, where the `default` suite in `behat.php` finds it. Follow the user story format, user types and test data rules in `docs/testing.md`.
-- Untagged scenarios run through an HTTP client without JavaScript. Tag a scenario `@javascript` only when the behavior needs JavaScript, because it runs in Chrome and is slower. Status code steps work only in untagged scenarios.
-- `@skipped` excludes a feature or scenario from every profile. CI splits scenarios across runners by tag: see https://www.vortextemplate.com/docs/development/testing/behat#profiles before tagging `@p1` or `@smoke`.
-- Put files that a scenario uploads in `tests/behat/fixtures`, the Mink `files_path`.
-- List the available steps with `ahoy test-bdd -- --definitions=l` and use an existing step whenever one fits. When no step fits, use the `create-behat-step` skill. Project steps live in `tests/behat/bootstrap/FeatureContext.php`.
-
-### Run them
-
-Scenarios run against the provisioned local site, so the stack must be up and the site provisioned.
-
-```shell
-ahoy test-bdd tests/behat/features/<name>.feature
-ahoy test-bdd -- --tags=@<tag>
-ahoy test-bdd -- --strict
-```
-
-CI runs with `--strict`, which fails on undefined or pending steps, so run the strict form before you finish. A failing step saves an HTML capture of the page, plus a PNG for `@javascript` scenarios, in `.logs/screenshots`. Read it to see what the browser saw.
-
-### Test a permission gate
-
-```gherkin
-  Scenario: User without the create permission is denied the page form
-    Given I am logged in as a user with the permission "access content"
-    When I go to "/node/add/page"
-    Then the response status code should be 403
-
-  Scenario: User with the create permission can open the page form
-    Given I am logged in as a user with the permission "create page content"
-    When I go to "/node/add/page"
-    Then the response status code should be 200
-```
-
-### Fixtures
-
-Scenarios share the local site's database with you. Content, users and terms created through the creation steps, such as `Given the following "page" content exists:`, are deleted after the scenario. Anything a scenario creates another way, such as by submitting a form, stays in the database, so give it a `[TEST]` title and don't let a later scenario depend on it.
 
 ## Verify the real code path
 
