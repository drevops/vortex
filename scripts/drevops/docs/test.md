# Test

DrevOps supports running PHPUnit and Behat tests.

It uses a wrapper script [test.sh](../test.sh) to proxy calls and support custom
workflows.

This script is wrapped with several Ahoy commands for simplicity of use.

Entrypoint command is

    ahoy test

There are also per-test type commands:

    ahoy test-unit

    ahoy test-kernel

    ahoy test-functional

    ahoy test-bdd

The type of tests can be overwritten by setting an environment variable
`DREVOPS_TEST_TYPE` to hyphen-delimited test type value: `unit-kernel-functional-bdd`.

Running of all tests can be skipped by setting `DREVOPS_TEST_SKIP` variable to `1`.

## PHPUnit

DrevOps uses Drupal core's `core/phpunit.xml.dist` PHPUnit configuration by
default. This is done to benefit from the upstream test bootstrap process. The
configuration file for every suite is specified in `DREVOPS_TEST_<SUITE>_CONFIG`
variable and may be overridden.

At the same time, consumer sites would not want to run core and contrib tests so
DrevOps provides a mechanism to only execute tests from the site's custom
modules and themes.

To make the test discoverable, it has to have `@group site:<suite_type>` annotation:

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

In addition, the groups are controlled via the `DREVOPS_TEST_<SUITE>_GROUP`
environment variables, allowing to override specific test group when needed
without changing the code, for example, when need to isolate test runs in CI.
These variables have default value of `site:<suite>`.

### Reporting

Test reports produced if `DREVOPS_TEST_REPORTS_DIR` is set. They are stored in
`$DREVOPS_TEST_REPORTS_DIR/phpunit` directory separated into multiple files
and named after the suite name. These reports are usually used in CI to track
tests performance and stability.

## Behat

DrevOps provides full Behat support, including configuration in [behat.yml](behat.yml)
and a [browser container](docker-compose.yml) to run interactive tests.

It also provides additional configuration:

1. DrupalExtension - an extension to work with Drupal.
2. Behat steps - a library of re-usable Behat steps designed for working with Drupal.
2. Screenshot extension - extension to capture screenshots on-demand and on failure.
3. Progress formatter - extension to show progress as TAP and fails inline.
4. Parallel profiles - configuration to allow running tests in parallel.

### Profiles

Behat `default` profile configured with sensible defaults to allow running Behat
with Drupal extension.

The profile can be overridden using `DREVOPS_TEST_BEHAT_PROFILE` environment variable.

### Skipping tests

Add `@skipped` tag to failing tests if you would like to skip them.

### Parallel runs

In CI, Behat tests can be tagged to be split between multiple runners. The tags
are then used by profiles with the identical names to run them.

Out of the box, DrevOps provides support for unlimited parallel runners, but only
2 parallel profiles `p0` and `p1`: a feature can be tagged by either `p0` or `p1`
to run in a dedicated runner, or with both tags to run in both runners. Note that
you can easily add more `p*` profiles in your `behat.yml` by copying existing `p1`
profile and changing several lines.

Untagged feature will always run in the first runner.

If CI has only one runner - a `default` profile will be used and all tests
(except for those that tagged with `skipped`) will be run.

### Screenshots

Test screenshots are stored into `tests/behat/screenshots` location by default,
which can be overwritten using `BEHAT_SCREENSHOT_DIR` variable (courtesy of
`drevops/behat-screenshot` package). In CI, screenshots are stored as artifacts.

### Format

Out of the box, DrevOps comes with `drevops/behat-format-progress-fail` Behat
output formatter to show progress as TAP and fails inline. This allows to
continue test runs after failures while maintaining a minimal output.

The format can be controlled `DREVOPS_TEST_BEHAT_FORMAT` environment variable.

### Reporting

Test reports produced if `DREVOPS_TEST_REPORTS_DIR` is set. They are stored in
`$DREVOPS_TEST_REPORTS_DIR/behat` directory. These reports are usually used in
CI to track tests performance and stability.

### FeatureContext

The [FeatureContext.php](tests/behat/FeatureContext.php) file comes with included
steps from `drevops/behat-steps` package.

Custom steps can be added into this file.
