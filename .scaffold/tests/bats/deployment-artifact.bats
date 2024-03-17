#!/usr/bin/env bats
#
# Test for CircleCI lifecycle.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load _helper.bash
load _helper.deployment.bash


@test "Missing or Invalid DREVOPS_DEPLOY_TYPES" {
  substep "Swap to ${LOCAL_REPO_DIR}"
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_DEPLOY_TYPES=""
  run ahoy deploy
  assert_failure

  assert_output_contains "Missing required value for DREVOPS_DEPLOY_TYPES. Must be a combination of comma-separated values (to support multiple deployments): code, docker, webhook, lagoon."

  popd >/dev/null
}

