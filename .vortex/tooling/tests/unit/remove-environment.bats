#!/usr/bin/env bats
##
# Unit tests for the 'vortex-remove-environment' script.
#
# The router resolves the hosting platform to the
# 'remove-environment-<platform>' sibling that implements the removal; these
# tests cover platform resolution and dispatch. The removal logic is owned by
# the sibling scripts.
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Remove environment: missing platform" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_REMOVE_ENVIRONMENT_PLATFORM
  unset VORTEX_PLATFORM
  run .vortex/tooling/src/vortex-remove-environment
  assert_failure
  assert_output_contains "Missing hosting platform. Set VORTEX_PLATFORM or VORTEX_REMOVE_ENVIRONMENT_PLATFORM."

  popd >/dev/null || exit 1
}

@test "Remove environment: unsupported platform" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_REMOVE_ENVIRONMENT_PLATFORM
  export VORTEX_PLATFORM=acquia
  run .vortex/tooling/src/vortex-remove-environment
  assert_failure
  assert_output_contains 'Unsupported hosting platform "acquia". Use one of: lagoon.'

  popd >/dev/null || exit 1
}

@test "Remove environment: platform from VORTEX_PLATFORM dispatches to Lagoon" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_sibling "vortex-remove-environment-lagoon" "Stub remove-environment-lagoon called."

  unset VORTEX_REMOVE_ENVIRONMENT_PLATFORM
  export VORTEX_PLATFORM=lagoon
  run .vortex/tooling/src/vortex-remove-environment
  assert_success
  assert_output_contains "Stub remove-environment-lagoon called."

  popd >/dev/null || exit 1
}

@test "Remove environment: VORTEX_REMOVE_ENVIRONMENT_PLATFORM overrides VORTEX_PLATFORM" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_sibling "vortex-remove-environment-lagoon" "Stub remove-environment-lagoon called."

  export VORTEX_PLATFORM=acquia
  export VORTEX_REMOVE_ENVIRONMENT_PLATFORM=lagoon
  run .vortex/tooling/src/vortex-remove-environment
  assert_success
  assert_output_contains "Stub remove-environment-lagoon called."

  popd >/dev/null || exit 1
}
