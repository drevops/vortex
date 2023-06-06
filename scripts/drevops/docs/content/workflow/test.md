# ✅ Test

DrevOps supports running Unit (PHPUnit) and BDD (Behat) tests.

It uses a router script [`scripts/drevops/test.sh`](../../../../scripts/drevops/test.sh)
to proxy calls to test type specific scripts and support custom workflows.

For local development, the tests can be run using handy Ahoy commands:

```bash
ahoy test # Run all tests

ahoy test-unit # Run Unit tests

ahoy test-kernel # Run Kernel tests

ahoy test-functional # Run Functional tests

ahoy test-bdd # Run BDD tests
```

In CI, tests are run by calling the [`scripts/drevops/test.sh`](../../../../scripts/drevops/test.sh)
script directly.

The type of tests can be overwritten by setting an environment variable
`$DREVOPS_TEST_TYPE` to a comma-separated list of test type
values: `$DREVOPS_TEST_TYPE=unit,kernel,functional,bdd`. This is useful when you
want to limit the scope of tests to run in CI for debugging purposes - simply
set a variable in CI UI and re-run the build.

And you can set `$DREVOPS_TEST_SKIP` variable to `1` to skip running of all
tests. This is, again, useful when debugging CI builds.

## Unit testing

DrevOps uses PHPUnit as a framework for Unit testing.

It is configured to use Drupal core's `core/phpunit.xml.dist` configuration by
default. This is done to benefit from the upstream test bootstrap process. The
configuration file for every suite is specified in `$DREVOPS_TEST_<SUITE>_CONFIG`
variable and may be overridden.

### Grouping

Normally, consumer sites would not want to run Drupal core and contrib tests so
DrevOps provides a mechanism to only run tests for the site's custom
modules and themes code.

To make the test discoverable, it has to have `@group site:<suite>`
annotation:

```php
<?php

namespace Drupal\Tests\ys_core\Unit;

/**
 * Class YsCoreExampleUnitTest.
 *
 * Example test case class.
 *
 * @group site:unit
 */
class YsCoreExampleUnitTest extends YsCoreUnitTestBase {
...
}
```

This approach is more flexible than filtering file by name using the suite
suffix (`*Unit*`, `*Kernel*`, `*Functional*` etc.) or supporting a custom
suit class discovery service.

In addition, the groups are controlled via the `$DREVOPS_TEST_UNIT_GROUP`,
`$DREVOPS_TEST_KERNEL_GROUP` and `$DREVOPS_TEST_FUNCTIONAL_GROUP`environment
variables. This allows to override specific test group when needed without
changing the code, for example, when need to isolate test runs in CI.
These variables have default value of `site:<suite>`.

### Reporting

Test reports are stored in `$DREVOPS_TEST_REPORTS_DIR/phpunit` directory
separated into multiple files and named after the suite name.
These reports are usually used in CI to track tests performance and stability.

### Scaffold

DrevOps provides a Unit test scaffold for custom [modules](../../../../web/modules/custom/ys_core/tests/src),
[themes](../../../../web/themes/custom/your_site_theme/tests/src) and
[scripts](../../../../tests/phpunit).

These tests already run in CI when you install DrevOps and can be used as a
starting point for writing your own.

#### Drupal settings tests

DrevOps provides a [Drupal settings tests](../../../../tests/phpunit/DrupalSettingsTest.php)
to test that Drupal settings are correct based on the environment type the site
is running: with the number of custom modules multiplied by the number of
environment types, it is easy to miss certain settings which may lead to
unexpected issues with deployments.

It is intended to be used in a consumer site and kept up-to-date with the
changes to the `settings.php` file.

#### CI configuration tests

DrevOps provides a [CI configuration tests](../../../../tests/phpunit/CircleCiConfigTest.php)
to assert that CI configuration is correct. It is intended to be used in a consumer
site and kept up-to-date with the CI configurations.

For example, there are tests for the regular expressions that control for which
branches the deployment job runs. Such test makes sure that there are no
unexpected surprises during the consumer site release to production.

## BDD testing

DrevOps uses Behat for Behavior-Driven Development (BDD) testing.

It provides full Behat support, including configuration in [behat.yml](../../../../behat.yml)
and a [browser container](../../../../docker-compose.yml) to run interactive tests.

It also provides additional features:

1. [Behat Drupal Extension](https://github.com/drupalprojects/drupalextension) - an extension to work with Drupal.
2. [Behat steps](https://github.com/drevops/behat-steps) - a library of re-usable Behat steps.
2. [Behat Screenshot](https://github.com/drevops/behat-screenshot) - extension to capture screenshots on-demand and on failure.
3. [Behat Progress formatter](https://github.com/drevops/behat-format-progress-fail) - extension to show progress as TAP and fails inline.
4. Parallel profiles - configuration to allow running tests in parallel.

### FeatureContext

The [FeatureContext.php](../../../../tests/behat/bootstrap/FeatureContext.php) file comes with
included steps from [Behat steps](https://github.com/drevops/behat-steps) package.

Custom steps can be added into this file.

### Profiles

Behat `default` profile configured with sensible defaults to allow running Behat
with provided extensions.

The profile can be overridden using `$DREVOPS_TEST_BEHAT_PROFILE` environment
variable.

### Parallel runs

In CI, Behat tests can be tagged to be split between multiple runners. The tags
are then used by profiles with the identical names to run them.

Out of the box, DrevOps provides support for unlimited parallel runners, but
only 2 parallel profiles `p0` and `p1`: a feature can be tagged by either `@p0`
or `@p1`to run in a dedicated runner, or with both tags to run in both runners.

Note that you can easily add more `p*` profiles in your `behat.yml` by copying
existing `p1`profile and changing several lines.

Features without `@p*` tags will always run in the first CI runner, so even
if you forget to tag the feature, it will still be allocated to a runner.

If CI has only one runner - a `default` profile will be used and all tests
(except for those that tagged with `@skipped`) will be run.

### Skipping tests

Add `@skipped` tag to a feature or scenario to exclude it from the test run.

### Screenshots

Test screenshots are stored into `tests/behat/screenshots` location by default,
which can be overwritten using `$BEHAT_SCREENSHOT_DIR` variable (courtesy of
[Behat Screenshot](https://github.com/drevops/behat-screenshot) package). In CI, screenshots are stored as artifacts
and are accessible in the Artifacts tab.

### Format

Out of the box, DrevOps comes with [Behat Progress formatter](https://github.com/drevops/behat-format-progress-fail)
Behat output formatter to show progress as TAP and fails inline. This allows to
continue test runs after failures while maintaining a minimal output.

The format can be controlled `$DREVOPS_TEST_BEHAT_FORMAT` environment variable.

### Reporting

Test reports produced if `$DREVOPS_TEST_REPORTS_DIR` is set. They are stored in
`$DREVOPS_TEST_REPORTS_DIR/behat` directory. These reports are usually used in
CI to track tests performance and stability.

### Scaffold

DrevOps provides a BDD test scaffold for custom [modules](../../../../web/modules/custom/ys_core/tests/src)
and [themes](../../../../web/themes/custom/your_site_theme/tests/src).

These tests already run in CI when you install DrevOps and can be used as a
starting point for writing your own.
