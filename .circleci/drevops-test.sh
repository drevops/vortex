#!/usr/bin/env bash
##
# Run DrevOps tests in CI.
#
# This file is removed after install/update.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

[ "$(git config --global user.name)" == "" ] && echo "==> Configuring global git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" == "" ] && echo "==> Configuring global git user email" && git config --global user.email "someone@example.com"

# Create stub of local framework.
docker network create amazeeio-network || true

echo "==> Lint scripts code"
scripts/drevops/lint-scripts.sh

echo "==> Check spelling"
scripts/drevops/lint-spelling.sh

echo "==> Test helpers"
bats tests/bats/helpers.bats --tap

echo "==> Test installation"
bats tests/bats/install_parameters.bats --tap
bats tests/bats/install_integrations.bats --tap
bats tests/bats/install_initial.bats --tap
bats tests/bats/install_existing.bats --tap
bats tests/bats/install_demo.bats --tap
bats tests/bats/env.bats --tap
bats tests/bats/clean.bats --tap
bats tests/bats/update.bats --tap

index="${CIRCLE_NODE_INDEX:-*}"
echo "==> Test workflows (${index})"
# bats "tests/bats/workflow${index}.bats" --tap
bats "tests/bats/workflow0.bats" --tap
