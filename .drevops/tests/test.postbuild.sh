#!/usr/bin/env bash
##
# Run DrevOps CI post-build tests.
#
# LCOV_EXCL_START

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

SCRIPTS_DIR="${ROOT_DIR}/scripts/drevops"

TEST_DIR="${ROOT_DIR}/.drevops/tests"

# ------------------------------------------------------------------------------

[ ! -d "${TEST_DIR}/node_modules" ] && echo "  > Install test Node dependencies." && npm --prefix="${TEST_DIR}" ci

bats() {
  if [ -n "${DREVOPS_TEST_COVERAGE_DIR:-}" ]; then
    mkdir -p "${DREVOPS_TEST_COVERAGE_DIR}"
    kcov --include-pattern=.sh,.bash --include-path="${SCRIPTS_DIR}" --bash-parse-files-in-dir="${SCRIPTS_DIR}" --exclude-path=${TEST_DIR}/node_modules,${TEST_DIR}/vendor,${SCRIPTS_DIR}/installer,${SCRIPTS_DIR}/docs "${DREVOPS_TEST_COVERAGE_DIR}" "${TEST_DIR}/node_modules/.bin/bats" "$@"
  else
    "${TEST_DIR}/node_modules/.bin/bats" "$@"
  fi
}

bats "${TEST_DIR}/bats/circleci.bats"
