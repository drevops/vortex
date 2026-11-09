---
name: write-tests
description: Use when writing or fixing automated tests, reproducing a bug with a failing test before fixing it, deciding whether a change deserves a test, or choosing between unit, kernel, functional and end-to-end scenario tests in this project.
---

# Write tests

Write the cheapest test that proves the behavior, and watch it fail before trusting it to pass.

Run every command from the project root, 1 command per Bash call.

## Read the project conventions

Read `docs/testing.md` before writing anything. It records the test structure, base classes, test data rules and examples this project agreed on, and it wins wherever it differs from this skill. Then open 1 or 2 existing tests of the same type in the same module and match their layout.

## Decide whether the behavior deserves a test

Commit a new test only when both conditions hold:

- A plausible edit could break the behavior without anyone noticing before release.
- The breakage costs something: wrong or lost data, access granted to the wrong user, broken content editing, a response shape an integration depends on, or a regression that has happened before.

Usually worth a test: business rules and data transforms, access checks on routes and entities, cache invalidation after a save, form validation, the structure of an API or feed response, and any code with a bug history.

Usually not worth a test: a label or copy change (unless the wording is a legal requirement), markup or spacing that visual regression already covers, behavior that Drupal core or a contributed module already tests, and a test that restates the implementation line by line.

Name the edit that would break the behavior. When you can't, skip the test. As a check, remove the assertion you plan to commit: if nothing else in the test can fail, the assertion guarded nothing.

A cosmetic or layout fix is proven by looking at the page. Take a screenshot before and after, and don't invent a failing assertion for it.

Every behavior bug still gets a reproducing test, as the next section describes.

## Reproduce a bug before fixing it

1. Write a test that drives the path the user took: the same route, form, service call or Drush command, with the input from the report. Don't re-implement the suspected logic inside the test.
2. Run only that test and confirm it fails with the symptom from the report. A failure for another reason, such as a module missing from the test setup, doesn't count. When the test passes, the bug isn't reproduced yet: change the input or the path until it fails for the reported reason.
3. Fix the code.
4. Run the same test and confirm it passes. Then run the whole suite for that test type.
5. Keep a case for the broken input next to the case for the fixed behavior when that helps a reader see why the code is written the way it is. Name each case after the behavior, never after a ticket.

## Pick the cheapest test type

Climb only as high as the assertion needs. Each step up costs more time on every run, and more setup to keep working. Prefer many cheap tests for the logic and 1 expensive test for the wiring.

| Type                  | Base class                                            | Directory under `web/modules/custom/<module>/` | Use for                                                                                     |
|-----------------------|-------------------------------------------------------|------------------------------------------------|---------------------------------------------------------------------------------------------|
| Unit                  | `Drupal\Tests\UnitTestCase`                           | `tests/src/Unit`                               | A class whose collaborators can be mocked: no database, no container                         |
| Kernel                | `Drupal\KernelTests\KernelTestBase`                   | `tests/src/Kernel`                             | Services, entities, fields, plugins, configuration and queries against a real database      |
| Functional            | `Drupal\Tests\BrowserTestBase`                        | `tests/src/Functional`                         | Routing, forms, redirects and status codes over real HTTP requests                           |
| Functional JavaScript | `Drupal\FunctionalJavascriptTests\WebDriverTestBase`  | `tests/src/FunctionalJavascript`               | Behavior that needs JavaScript in Chrome                                                     |

A kernel test installs only the modules in `$modules`. A functional test installs a whole site for every test method, so it is the slowest PHPUnit type by far.

The last step is a Behat scenario: a user journey across pages, run against the provisioned site. Spend scenarios on the journeys that matter most, such as logging in, editing content or submitting a form, and keep edge cases in cheaper tests.

## Avoid tests that pass without proving anything

- **Assertions over an empty result.** A check that "no item has X" or "every item has Y" passes when there are no items. Assert the count, or the presence of a known item, before checking each one.
- **Negative assertions on the wrong page.** "The page doesn't contain X" passes on a 404, an access denied page or an error page. Assert that the page loaded and shows expected content before asserting what is absent.
- **Tests that never run.** A test tagged `skipped`, a test outside the collected directories or a filter that matches nothing reports success without running your code. After adding a test, confirm that the run lists it, or that the test count grew by the number you added.
- **The anonymous 403 trap.** A 403 for an anonymous visitor doesn't prove that a permission is enforced. A route that also requires login rejects anonymous visitors before it checks the permission, and a permission granted to the `authenticated` role lets every logged-in user through. Prove a gate with a logged-in user who lacks the permission (expect 403) and one who has it (expect 200). In a route's `_permission` requirement, `,` means all of the listed permissions and `+` means any of them.
- **A guard you never saw fail.** For an access or validation test, remove the permission or the check once, run the test, watch it fail, then restore the code.

## Keep fixtures clean

- Prefix test content with `[TEST]`, as `docs/testing.md` requires, so leftovers are recognizable.
- Create every entity, user and setting a test relies on inside the test. Never depend on content that happens to exist in your local database, because CI and other developers start from a different one.
- Restore any configuration or state a test changes, in a step that runs even when the test fails.

## Unit, kernel and functional tests

### Run them

Kernel and functional tests need the running stack (`ahoy up`), because they use the `database`, `nginx` and `chrome` containers that `phpunit.xml` points at.

```shell
ahoy test-unit
ahoy test-kernel
ahoy test-functional
ahoy test-functional-javascript
ahoy test web/modules/custom/<module>/tests/src/Kernel/<Name>Test.php
ahoy test -- --filter=<testMethodName>
ahoy test -- --group=<group>
```

`ahoy test` runs all 4 suites. The `unit` suite also collects the project-level tests in `tests/phpunit`. When you change `web/sites/default/settings.php` or a file it includes, update the settings tests in `tests/phpunit/Drupal/` in the same change.

### What the strict configuration means for you

`phpunit.xml` stops the run at the first error, failure, warning or risky test, and fails it on any skipped test or PHPUnit deprecation. In practice:

- A test with no assertion is risky and stops the run. So is a test that prints output.
- `markTestSkipped()` fails the run. To park a test, add `#[Group('skipped')]`, which `phpunit.xml` excludes, and remove it again before you finish.
- Write test metadata as attributes, as the shipped tests do: `#[Group('...')]`, `#[DataProvider('...')]`, imported from `PHPUnit\Framework\Attributes`. A docblock annotation such as `@dataProvider` triggers a PHPUnit deprecation that fails the run, and PHPUnit ignores docblock metadata entirely on a method that has any attribute. Use the attribute form even where an example in `docs/testing.md` shows a docblock annotation.
- Kernel, functional and functional JavaScript test classes carry `#[RunTestsInSeparateProcesses]`, as the shipped tests do. Copy it.
- Because the run stops at the first failure, a green filtered run says nothing about the rest of the suite. Run the full suite of each type you touched before you finish.
- CI fails the build when line coverage drops below `VORTEX_CI_CODE_COVERAGE_THRESHOLD` (default `90`). Files that no test runs count at 0%, so a new class without tests lowers coverage. The summary is in `.logs/coverage/phpunit/coverage.txt`.

See https://www.vortextemplate.com/docs/development/testing/phpunit for the configuration, reports and CI behavior.

### Shared base classes

`web/modules/custom/sw_base/tests/src/` provides `SwBaseUnitTestBase`, `SwBaseKernelTestBase` and `SwBaseFunctionalTestBase`. They add the `ArrayTrait`, `AssertTrait`, `MockTrait` and `ReflectionTrait` helpers from `tests/src/Traits/`, and the functional base sets the `stark` theme. Read the traits before writing a helper of your own, and extend these bases when a test needs them.

### Data providers

Put the cases of 1 behavior in a data provider instead of copying the test method. Follow the naming and placement rules in `docs/testing.md`, and give every row a key that describes the case, so a failure names it:

```php
#[DataProvider('dataProviderTitleToSlug')]
public function testTitleToSlug(string $title, string $expected): void {
  $this->assertSame($expected, my_module_slug($title));
}

public static function dataProviderTitleToSlug(): \Iterator {
  yield 'spaces become hyphens' => ['[TEST] Annual report', 'test-annual-report'];
  yield 'empty title' => ['', ''];
}
```

A provider multiplies the cost of its test. 10 rows on a functional test install 10 sites, so keep the matrix in a unit or kernel test and cover the HTTP wiring with 1 functional test.

### Concrete checks against false passes

Assert the precondition before the loop, and the page before the absence:

```php
$this->assertCount(3, $terms);
foreach ($terms as $term) {
  $this->assertStringStartsWith('[TEST]', $term->label());
}

$this->assertSession()->statusCodeEquals(200);
$this->assertSession()->pageTextContains('[TEST] Public page');
$this->assertSession()->pageTextNotContains('[TEST] Private note');
```

Test a permission gate with 2 logged-in users in a functional test (`$modules` includes `node`):

```php
public function testPageCreateAccess(): void {
  $this->drupalCreateContentType(['type' => 'page']);

  $this->drupalLogin($this->drupalCreateUser(['access content']));
  $this->drupalGet('node/add/page');
  $this->assertSession()->statusCodeEquals(403);

  $this->drupalLogin($this->drupalCreateUser(['create page content']));
  $this->drupalGet('node/add/page');
  $this->assertSession()->statusCodeEquals(200);
}
```

For a whole family of routes, a kernel test can pin each route's requirement, so loosening it fails a test without any HTTP request:

```php
$this->container->get('router.builder')->rebuild();
$route = $this->container->get('router.route_provider')->getRouteByName('my_module.report');
$this->assertSame('view my_module reports', $route->getRequirement('_permission'));
```

### Fixtures

Kernel and functional tests install a throwaway site in the `SIMPLETEST_DB` database under a table prefix. Nothing they create reaches the local site, and nothing from the local site exists in them: create content types, fields, users and content in `setUp()` or in the test. In a kernel test, list every module the code needs in `$modules`, and install the entity schemas and configuration it reads with `installEntitySchema()` and `installConfig()`.

## Behat scenarios

### Write them

- Put each feature in `tests/behat/features/<name>.feature`, where the `default` suite in `behat.php` finds it. Follow the user story format, user types and test data rules in `docs/testing.md`.
- Untagged scenarios run through an HTTP client without JavaScript. Tag a scenario `@javascript` only when the behavior needs JavaScript, because it runs in Chrome and is slower. Status code steps work only in untagged scenarios.
- `@skipped` excludes a feature or scenario from every profile. CI splits scenarios across runners by tag: see https://www.vortextemplate.com/docs/development/testing/behat#profiles before tagging `@p1` or `@smoke`.
- Put files that a scenario uploads in `tests/behat/fixtures`, the Mink `files_path`.
- List the available steps with `ahoy test-bdd -- --definitions=l` and use an existing step whenever one fits. When no step fits, use the `create-behat-step` skill. Project steps live in `tests/behat/bootstrap/FeatureContext.php`.

### Run them

Scenarios run against the provisioned local site, so the stack must be up and the site provisioned.

```shell
ahoy test-bdd tests/behat/features/<name>.feature
ahoy test-bdd -- --tags=@<tag>
ahoy test-bdd -- --strict
```

CI runs with `--strict`, which fails on undefined or pending steps, so run the strict form before you finish. A failing step saves an HTML capture of the page, plus a PNG for `@javascript` scenarios, in `.logs/screenshots`. Read it to see what the browser saw.

### Test a permission gate

```gherkin
  Scenario: User without the create permission is denied the page form
    Given I am logged in as a user with the permission "access content"
    When I go to "/node/add/page"
    Then the response status code should be 403

  Scenario: User with the create permission can open the page form
    Given I am logged in as a user with the permission "create page content"
    When I go to "/node/add/page"
    Then the response status code should be 200
```

### Fixtures

Scenarios share the local site's database with you. Content, users and terms created through the creation steps, such as `Given the following "page" content exists:`, are deleted after the scenario. Anything a scenario creates another way, such as by submitting a form, stays in the database, so give it a `[TEST]` title and don't let a later scenario depend on it.

## Verify the real code path

Passing tests prove the logic in isolation. Before calling a change to runtime behavior done, run the changed code on the local site and check the result:

- After changing routes, services, plugins or YAML definitions, rebuild caches with `ahoy drush cr`.
- Run a changed Drush command with `ahoy drush <command>`.
- To call a service or inspect entities, write a script under `.artifacts/tmp/`, such as `.artifacts/tmp/check-report.php`, and run it with `ahoy drush php:script .artifacts/tmp/check-report.php`. Never use `drush php:eval`.
- Open a changed page as an administrator with the link from `ahoy login`.
- Check recent errors with `ahoy drush watchdog:show --count=20`.

Report what you ran and what it showed, next to the test results.

## Before you finish

- Each new or changed test fails without the fix and passes with it.
- The full suite of every test type you touched passes.
- `ahoy lint` passes, because it checks test code too.
- The change contains no parked tests and no `[TEST]` leftovers in fixtures you meant to delete.
