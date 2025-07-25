#!/usr/bin/env bash
##
# Run Vortex workflow tests.
#
# LCOV_EXCL_START

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

SCRIPTS_DIR="${ROOT_DIR}/scripts/vortex"

TEST_DIR="${ROOT_DIR}/.vortex/tests"

# ------------------------------------------------------------------------------

# Configure git username and email if it is not set.
[ "$(git config --global user.name)" = "" ] && echo "==> Configuring global test git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" = "" ] && echo "==> Configuring global test git user email" && git config --global user.email "someone@example.com"

# Create stub of local framework.
docker network create amazeeio-network 2>/dev/null || true

index="${TEST_NODE_INDEX:-*}"
echo "==> Run workflow functional tests (${index})."

[ ! -d "${TEST_DIR}/node_modules" ] && echo "  > Install test Node dependencies." && yarn --cwd="${TEST_DIR}" install --frozen-lockfile
[ ! -d "${TEST_DIR}/vendor" ] && echo "  > Install test PHP dependencies." && composer --working-dir="${TEST_DIR}" install --no-interaction --no-progress --optimize-autoloader

bats() {
  pushd "${ROOT_DIR}" >/dev/null || exit 1
  if [ -n "${VORTEX_DEV_TEST_COVERAGE_DIR:-}" ]; then
    mkdir -p "${VORTEX_DEV_TEST_COVERAGE_DIR}"
    kcov --include-pattern=.sh,.bash --bash-parse-files-in-dir="${SCRIPTS_DIR}","${TEST_DIR}" --exclude-pattern=vendor,node_modules "${VORTEX_DEV_TEST_COVERAGE_DIR}" "${TEST_DIR}/node_modules/.bin/bats" "$@"
  else
    "${TEST_DIR}/node_modules/.bin/bats" "$@"
  fi
  popd >/dev/null || exit 1
}

phpunit() {
  pushd "${TEST_DIR}" >/dev/null || exit 1
  "./vendor/bin/phpunit" "$@"
  popd >/dev/null || exit 1
}

# Not every test has a coverage report, so we create an empty directory
# to avoid errors in CI.
# @see https://github.com/actions/upload-artifact/issues/255
if [ -n "${CI}" ]; then
  mkdir -p /tmp/.vortex-coverage-html
  touch "/tmp/.vortex-coverage-html/.empty-$(date +%Y%m%d%H%M%S)"
fi

# Run workflow based on index using switch-case.
case ${index} in

  0)
    phpunit "${TEST_DIR}"/phpunit
    ;;

  1)
    bats "${TEST_DIR}"/bats/e2e/workflow.install.db.bats
    ;;

  2)
    bats "${TEST_DIR}"/bats/e2e/workflow.install.profile.bats
    ;;

  3)
    bats "${TEST_DIR}"/bats/e2e/workflow.docker-compose.bats
    bats "${TEST_DIR}"/bats/e2e/workflow.install.provision.bats
    bats "${TEST_DIR}"/bats/e2e/workflow.storage.image.bats
    # Disabled due to intermittent failures.
    # @see https://github.com/drevops/vortex/issues/893
    # bats "${TEST_DIR}"/bats/e2e/workflow.storage.image_cached.bats
    ;;

  *)
    phpunit "${TEST_DIR}"/phpunit
    bats "${TEST_DIR}"/bats/e2e/workflow.install.db.bats
    bats "${TEST_DIR}"/bats/e2e/workflow.install.profile.bats
    bats "${TEST_DIR}"/bats/e2e/workflow.docker-compose.bats
    bats "${TEST_DIR}"/bats/e2e/workflow.install.provision.bats
    bats "${TEST_DIR}"/bats/e2e/workflow.storage.image.bats
    # Disabled due to intermittent failures.
    # @see https://github.com/drevops/vortex/issues/893
    # bats "${TEST_DIR}"/bats/e2e/workflow.storage.image_cached.bats
    ;;
esac
