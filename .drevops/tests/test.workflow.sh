#!/usr/bin/env bash
##
# Run DrevOps workflow tests.
#
# LCOV_EXCL_START

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

SCRIPTS_DIR="${ROOT_DIR}/scripts/drevops"

TEST_DIR="${ROOT_DIR}/.drevops/tests"

# ------------------------------------------------------------------------------

# Configure git username and email if it is not set.
[ "$(git config --global user.name)" = "" ] && echo "==> Configuring global git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" = "" ] && echo "==> Configuring global git user email" && git config --global user.email "someone@example.com"

# Create stub of local framework.
docker network create amazeeio-network 2>/dev/null || true

index="${CIRCLE_NODE_INDEX:-*}"
echo "==> Run workflow functional tests (${index})."
[ ! -d "${TEST_DIR}/node_modules" ] && echo "  > Install test Node dependencies." && npm --prefix="${TEST_DIR}" ci

bats() {
  pushd "${ROOT_DIR}" >/dev/null || exit 1
  if [ -n "${DREVOPS_DEV_TEST_COVERAGE_DIR:-}" ]; then
    mkdir -p "${DREVOPS_DEV_TEST_COVERAGE_DIR}"
    kcov --include-pattern=.sh,.bash --bash-parse-files-in-dir="${SCRIPTS_DIR}","${TEST_DIR}" --exclude-pattern=vendor,node_modules "${DREVOPS_DEV_TEST_COVERAGE_DIR}" "${TEST_DIR}/node_modules/.bin/bats" "$@"
  else
    "${TEST_DIR}/node_modules/.bin/bats" "$@"
  fi
  popd >/dev/null || exit 1
}

# Run workflow based on index using switch-case.
case ${index} in

  0)
    bats "${TEST_DIR}"/bats/workflow.smoke.bats
    bats "${TEST_DIR}"/bats/workflow.storage.curl.bats
    ;;

  1)
    bats "${TEST_DIR}"/bats/workflow.install.bats
    bats "${TEST_DIR}"/bats/workflow.utilities.bats
    ;;

  2)
    # Disabled due to intermittent failures.
    # @see https://github.com/drevops/drevops/issues/893
    # bats "${TEST_DIR}"/bats/workflow.storage.image_cached.bats
    bats "${TEST_DIR}"/bats/workflow.storage.image.bats
    ;;

  *)
    bats "${TEST_DIR}"/bats/workflow.smoke.bats
    bats "${TEST_DIR}"/bats/workflow.install.bats
    bats "${TEST_DIR}"/bats/workflow.storage.image.bats
    bats "${TEST_DIR}"/bats/workflow.storage.image_cached.bats
    bats "${TEST_DIR}"/bats/workflow.storage.curl.bats
    bats "${TEST_DIR}"/bats/workflow.utilities.bats
    ;;
esac
