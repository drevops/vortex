#!/usr/bin/env bash
##
# Run DrevOps tests for deployment in CI.
#
# This file is removed after install/update.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Create stub of local framework.
docker network create amazeeio-network || true

index="${CIRCLE_NODE_INDEX:-*}"
echo "==> Test deployments (${index})"
bats "scripts/drevops/tests/bats/deployment${index}.bats" --tap
