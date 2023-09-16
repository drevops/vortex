#!/usr/bin/env bash
##
# Run DrevOps deployment tests.
#

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

SCRIPTS_DIR="scripts/drevops"

TEST_DIR="${SCRIPTS_DIR}/tests"

# ------------------------------------------------------------------------------

# Configure git username and email if it is not set.
[ "$(git config --global user.name)" = "" ] && echo "==> Configuring global git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" = "" ] && echo "==> Configuring global git user email" && git config --global user.email "someone@example.com"

# Create stub of local framework.
docker network create amazeeio-network 2>/dev/null || true

index="${CIRCLE_NODE_INDEX:-*}"
echo "==> Run deployment functional tests (${index})."
[ ! -d "${TEST_DIR}/node_modules" ] && echo "  > Install test Node dependencies." && npm --prefix="${TEST_DIR}" ci

bats() {
  if [ -n "${DREVOPS_TEST_COVERAGE_DIR:-}" ]; then
    mkdir -p "${DREVOPS_TEST_COVERAGE_DIR}"
    kcov --include-pattern=.sh,.bash --include-path="${SCRIPTS_DIR}" --bash-parse-files-in-dir="${SCRIPTS_DIR}" --exclude-path=${TEST_DIR}/node_modules,${TEST_DIR}/vendor,${SCRIPTS_DIR}/installer/scripts/drevops/docs "${DREVOPS_TEST_COVERAGE_DIR}" "${TEST_DIR}/node_modules/.bin/bats" "$@"
  else
    "${TEST_DIR}/node_modules/.bin/bats" "$@"
  fi
}

# shellcheck disable=SC2086
bats "${TEST_DIR}"/bats/deployment${index}.bats
