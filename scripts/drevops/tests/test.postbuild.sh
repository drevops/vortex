#!/usr/bin/env bash
##
# Run DrevOps post-build tests in CI.
#

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

TEST_DIR="scripts/drevops/tests"

# ------------------------------------------------------------------------------

[ ! -d "${TEST_DIR}/node_modules" ] && echo "  > Install test Node dependencies." && npm --prefix="${TEST_DIR}" ci
bats="${TEST_DIR}/node_modules/.bin/bats"

$bats "${TEST_DIR}/bats/circleci.bats"
