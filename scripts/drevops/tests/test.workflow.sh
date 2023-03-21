#!/usr/bin/env bash
##
# Run DrevOps workflow tests.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

TEST_DIR="scripts/drevops/tests"

# Create stub of local framework.
docker network create amazeeio-network 2> /dev/null || true

# Configure git username and email if it is not set.
[ "$(git config --global user.name)" = "" ] && echo "==> Configuring global git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" = "" ] && echo "==> Configuring global git user email" && git config --global user.email "someone@example.com"

index="${CIRCLE_NODE_INDEX:-*}"
echo "==> Run workflow functional tests (${index})."
[ ! -d "${TEST_DIR}/node_modules" ] && echo "  > Install test Node dependencies." && npm --prefix="${TEST_DIR}" ci
bats="${TEST_DIR}/node_modules/.bin/bats"

$bats "${TEST_DIR}/bats/workflow${index}.bats"
