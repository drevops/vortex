#!/usr/bin/env bats
##
# Unit tests for export-db router script.
#
# shellcheck disable=SC2030,SC2031

load ../_helper.bash

# Replaces a sibling tooling script with a stub that prints a marker. The router
# dispatches to siblings by explicit path, so a PATH-based mock cannot intercept
# them - the file itself must be replaced.
stub_sibling() {
  mkdir -p .vortex/tooling/src
  printf '#!/usr/bin/env bash\necho "%s"\n' "${2}" >".vortex/tooling/src/${1}"
  chmod +x ".vortex/tooling/src/${1}"
}

@test "export-db: Exports as a file in place when not on the host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export RUN_ON_HOST=0

  mock_docker=$(mock_command "docker")
  stub_sibling "export-db-file" "Exported in place."

  run .vortex/tooling/src/vortex-export-db
  assert_success
  assert_output_contains "Started database export."
  assert_output_contains "Exported in place."
  assert_output_contains "Finished database export."
  assert_equal "0" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "export-db: Exports as a file through Docker Compose on the host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export RUN_ON_HOST=1

  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Exported through Docker Compose." 1

  run .vortex/tooling/src/vortex-export-db
  assert_success
  assert_output_contains "Exported through Docker Compose."
  assert_output_contains "Finished database export."
  assert_equal "1" "$(mock_get_call_num "${mock_docker}")"
  assert_equal "compose exec -T cli ./vendor/drevops/vortex-tooling/src/vortex-export-db-file" "$(mock_get_call_args "${mock_docker}" 1)"

  popd >/dev/null
}

@test "export-db: Detects the host from the Docker CLI when the override is unset" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Exported through detected Docker." 1

  run .vortex/tooling/src/vortex-export-db
  assert_success
  assert_output_contains "Exported through detected Docker."
  assert_equal "1" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "export-db: Exports as a container image on the host when an image name is set" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export RUN_ON_HOST=1
  export VORTEX_EXPORT_DB_IMAGE="myorg/myapp"

  stub_sibling "export-db-image" "Exported as a container image."

  run .vortex/tooling/src/vortex-export-db
  assert_success
  assert_output_contains "Exported as a container image."
  assert_output_contains "Finished database export."

  popd >/dev/null
}
