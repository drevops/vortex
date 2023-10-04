#!/usr/bin/env bash
##
# Additional environment variables used in this project in CI.
# shellcheck disable=SC2034

# Proceed with Docker image deployment after it was exported.
DREVOPS_EXPORT_DB_DOCKER_DEPLOY_PROCEED=

# Directory to store exported code.
DREVOPS_EXPORT_CODE_DIR=

# Allow code linting failures.
DREVOPS_CI_LINT_ALLOW_FAILURE=0

# Allow tests failures.
DREVOPS_CI_TEST_ALLOW_FAILURE=0

# Test Behat profile to use in CI. If not set, the default profile will be used.
DREVOPS_TEST_BEHAT_PROFILE=

# Directory to store test results.
DREVOPS_CI_TEST_RESULTS=/tmp/tests

# Directory to store test artifacts.
DREVOPS_CI_ARTIFACTS=/tmp/artifacts
