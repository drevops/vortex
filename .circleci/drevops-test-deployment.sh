#!/usr/bin/env bash
##
# Run DrevOps tests for deployment in CI.
#
# This file is removed after install/update.
set -e

# Create stub of local framework.
docker network create amazeeio-network

index="${CIRCLE_NODE_INDEX:-*}"
echo "==> Test deployments (${index})"
bats "tests/bats/deployment${index}.bats" --tap
