#!/usr/bin/env bash
##
# Additional environment variables used in this project in CI.
# shellcheck disable=SC2034

# Proceed with Docker image deployment after it was exported.
DREVOPS_EXPORT_DB_DOCKER_DEPLOY_PROCEED=

# Directory to store exported code.
DREVOPS_EXPORT_CODE_DIR=

# Ignore PHPCS failures.
DREVOPS_CI_PHPCS_IGNORE_FAILURE=0

# Ignore PHPStan failures.
DREVOPS_CI_PHPSTAN_IGNORE_FAILURE=0

# Ignore PHPMD failures.
DREVOPS_CI_PHPMD_IGNORE_FAILURE=0

# Ignore Twigcs failures.
DREVOPS_CI_TWIGCS_IGNORE_FAILURE=0

# Ignore NPM linters failures.
DREVOPS_CI_NPM_LINT_IGNORE_FAILURE=0

# Ignore PHPUnit test failures.
DREVOPS_CI_PHPUNIT_IGNORE_FAILURE=0

# Ignore Behat test failures.
DREVOPS_CI_BEHAT_IGNORE_FAILURE=0

# Test Behat profile to use in CI. If not set, the `default` profile will be used.
DREVOPS_CI_BEHAT_PROFILE=

# Directory to store test results in CI.
DREVOPS_CI_TEST_RESULTS=/tmp/tests

# Directory to store test artifacts in CI.
DREVOPS_CI_ARTIFACTS=/tmp/artifacts
