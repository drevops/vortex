#!/usr/bin/env bash
##
# Run Drupal-Dev tests in CI.
#
set -e

[ "$(git config --global user.name)" == "" ] && echo "==> Configuring global git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" == "" ] && echo "==> Configuring global git user email" && git config --global user.email "someone@example.com"

echo "==> Lint scripts code"
scripts/lint-scripts.sh

echo "==> Check spelling"
scripts/check-spell.sh

echo "==> Test helpers"
bats tests/bats/helpers.bats --tap

echo "==> Test installation"
bats tests/bats/install_parameters.bats --tap
bats tests/bats/install_integrations.bats --tap
bats tests/bats/install_initial.bats --tap
bats tests/bats/install_existing.bats --tap
bats tests/bats/clean.bats --tap

index="${CIRCLE_NODE_INDEX:-*}"
echo "==> Test workflows (${index})"
bats "tests/bats/workflow${index}.bats" --tap
