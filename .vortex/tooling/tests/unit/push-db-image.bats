#!/usr/bin/env bats
##
# Unit tests for push-db-image script.
#
# The stub map markers use single quotes on purpose: the `${...}` must reach the
# generated stub literally and expand when the stub runs, not when the test
# calls stub_sibling - so SC2016 is suppressed here.
#
# shellcheck disable=SC2016,SC2030,SC2031

load ../_helper.bash

# Replaces a sibling tooling script with a stub that prints a marker. The script
# dispatches to siblings by explicit path, so a PATH-based mock cannot intercept
# them - the file itself must be replaced.
stub_sibling() {
  mkdir -p .vortex/tooling/src
  printf '#!/usr/bin/env bash\necho "%s"\n' "${2}" >".vortex/tooling/src/${1}"
  chmod +x ".vortex/tooling/src/${1}"
}

@test "push-db-image: Skips the push when it is not requested" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_EXPORT_DB_CONTAINER_REGISTRY_PUSH_PROCEED
  export VORTEX_EXPORT_DB_IMAGE="myorg/myapp"

  stub_sibling "push-container-registry" "Pushed the container image."

  run .vortex/tooling/src/vortex-push-db-image
  assert_success
  assert_output_contains "Skipped database container image push as VORTEX_EXPORT_DB_CONTAINER_REGISTRY_PUSH_PROCEED is not set to 1."
  assert_output_not_contains "Pushed the container image."

  popd >/dev/null
}

@test "push-db-image: Pushes the image when requested" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_EXPORT_DB_CONTAINER_REGISTRY_PUSH_PROCEED=1
  export VORTEX_EXPORT_DB_IMAGE="myorg/myapp"

  stub_sibling "push-container-registry" 'Pushed with map: ${VORTEX_PUSH_CONTAINER_REGISTRY_MAP}'

  run .vortex/tooling/src/vortex-push-db-image
  assert_success
  assert_output_contains "Started database container image push."
  assert_output_contains "Pushed with map: database=myorg/myapp"
  assert_output_contains "Finished database container image push."

  popd >/dev/null
}

@test "push-db-image: Resolves the image from VORTEX_DB_IMAGE when VORTEX_EXPORT_DB_IMAGE is not set" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_EXPORT_DB_CONTAINER_REGISTRY_PUSH_PROCEED=1
  unset VORTEX_EXPORT_DB_IMAGE
  export VORTEX_DB_IMAGE="myorg/fallback"

  stub_sibling "push-container-registry" 'Pushed with map: ${VORTEX_PUSH_CONTAINER_REGISTRY_MAP}'

  run .vortex/tooling/src/vortex-push-db-image
  assert_success
  assert_output_contains "Pushed with map: database=myorg/fallback"

  popd >/dev/null
}

@test "push-db-image: Fails when the image name is not specified" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_EXPORT_DB_CONTAINER_REGISTRY_PUSH_PROCEED=1
  unset VORTEX_EXPORT_DB_IMAGE
  unset VORTEX_DB_IMAGE

  run .vortex/tooling/src/vortex-push-db-image
  assert_failure
  assert_output_contains "Container image name is not specified."

  popd >/dev/null
}

@test "push-db-image: Fails when the container registry push fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_EXPORT_DB_CONTAINER_REGISTRY_PUSH_PROCEED=1
  export VORTEX_EXPORT_DB_IMAGE="myorg/myapp"

  mkdir -p .vortex/tooling/src
  printf '#!/usr/bin/env bash\nexit 1\n' >.vortex/tooling/src/vortex-push-container-registry
  chmod +x .vortex/tooling/src/vortex-push-container-registry

  run .vortex/tooling/src/vortex-push-db-image
  assert_failure
  assert_output_contains "Started database container image push."
  assert_output_not_contains "Finished database container image push."

  popd >/dev/null
}
