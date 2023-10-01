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
