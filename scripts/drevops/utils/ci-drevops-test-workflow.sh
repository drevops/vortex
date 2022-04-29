#!/usr/bin/env bash
##
# Run DrevOps workflow tests in CI.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

[ "$(git config --global user.name)" = "" ] && echo "==> Configuring global git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" = "" ] && echo "==> Configuring global git user email" && git config --global user.email "someone@example.com"

# Create stub of local framework.
docker network create amazeeio-network || true

index="${CIRCLE_NODE_INDEX:-*}"
echo "==> Test workflows (${index})."
bats "scripts/drevops/tests/bats/workflow${index}.bats" --tap
