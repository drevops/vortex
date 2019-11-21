#!/usr/bin/env bash
##
# Run tests in CI.
#

set -e

echo "==> Lint code"
ahoy lint

echo "==> Run Drupal unit tests"
ahoy test-unit

echo "==> Run Drupal kernel tests"
ahoy test-kernel

echo "==> Run Drupal functional tests"
ahoy test-functional

# Running Behat tests can be done in parallel, provided that you set
# build concurrency CircleCI UI to a number larger then 1 container and
# your tests are tagged with `p0`, `p1` etc. to assign tests to a specific
# build node.
#
# Using `progress_fail` format allows to get an instant feedback about
# broken tests without stopping all other tests or waiting for the build
# to finish. This is particularly useful for projects with large number
# of tests.
#
# We are also using --rerun option to overcome some false positives that
# could appear in browser-based tests. With this option, Behat remembers
# which tests failed during previous run and re-runs only them.
#
# Lastly, we copy test results (artifacts) out of containers and
# store them so that CircleCI could show them in 'Artifacts' tab. This is done
# outside of this script.

echo "==> Run BDD tests"
[ "${CIRCLE_NODE_TOTAL}" -gt "1" ] && BEHAT_PROFILE=p${CIRCLE_NODE_INDEX} && export BEHAT_PROFILE=${BEHAT_PROFILE}
ahoy test-bdd -- "--format=progress_fail" || ahoy test-bdd -- "--rerun --format=progress_fail"

