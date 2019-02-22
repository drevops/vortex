#!/usr/bin/env bash
##
# Run Drupal-Dev tests for deployment in CI.
#
set -e


index="${CIRCLE_NODE_INDEX:-*}"
echo "==> Test deployments (${index})"
bats "tests/bats/deployment${index}.bats" --tap
