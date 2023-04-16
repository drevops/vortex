#!/usr/bin/env bash
##
# Run DrevOps deployment tests.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Allow to override the test GitHub token with an available global token.
export TEST_GITHUB_TOKEN="${TEST_GITHUB_TOKEN:-$GITHUB_TOKEN}"

TEST_DIR="scripts/drevops/tests"

# ------------------------------------------------------------------------------

[ -n "${TEST_GITHUB_TOKEN}" ] || ( echo "[ERROR] The required TEST_GITHUB_TOKEN variable is not set. Tests will not proceed." && exit 1 )

# Configure git username and email if it is not set.
[ "$(git config --global user.name)" = "" ] && echo "==> Configuring global git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" = "" ] && echo "==> Configuring global git user email" && git config --global user.email "someone@example.com"

# Create stub of local framework.
docker network create amazeeio-network 2> /dev/null || true

index="${CIRCLE_NODE_INDEX:-*}"
echo "==> Run deployment functional tests (${index})."
[ ! -d "${TEST_DIR}/node_modules" ] && echo "  > Install test Node dependencies." && npm --prefix="${TEST_DIR}" ci
bats="${TEST_DIR}/node_modules/.bin/bats"

# shellcheck disable=SC2086
$bats "${TEST_DIR}"/bats/deployment${index}.bats
