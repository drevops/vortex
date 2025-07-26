#!/usr/bin/env bash
##
# Run Vortex tests in CI.
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

echo "==> Run common functional tests."
[ ! -d "${TEST_DIR}/node_modules" ] && echo "  > Install test Node dependencies." && yarn --cwd="${TEST_DIR}" install --frozen-lockfile

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

bats "${TEST_DIR}/bats/unit/helpers.bats"
bats "${TEST_DIR}/bats/unit/deploy-artifact.bats"
bats "${TEST_DIR}/bats/unit/deploy-container-registry.bats"
bats "${TEST_DIR}/bats/unit/deploy-lagoon.bats"
bats "${TEST_DIR}/bats/unit/deploy-webhook.bats"
bats "${TEST_DIR}/bats/unit/login-container-registry.bats"
bats "${TEST_DIR}/bats/unit/notify.bats"
bats "${TEST_DIR}/bats/unit/provision.bats"
bats "${TEST_DIR}/bats/unit/setup-ssh.bats"
bats "${TEST_DIR}/bats/unit/update-vortex.bats"
bats "${TEST_DIR}/bats/e2e/env.bats"
bats "${TEST_DIR}/bats/e2e/docker-compose.bats"
bats "${TEST_DIR}/bats/e2e/reset.bats"
bats "${TEST_DIR}/bats/e2e/update-vortex.bats"
