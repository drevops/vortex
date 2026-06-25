#!/usr/bin/env bats
##
# Unit tests for export-db router script.
#
# shellcheck disable=SC2030,SC2031

load ../_helper.bash

@test "export-db: Exports as a file in place when not on the host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export RUN_ON_HOST=0

  mock_docker=$(mock_command "docker")
  mock_export_db_file=$(mock_command ".vortex/tooling/src/export-db-file")
  mock_set_output "${mock_export_db_file}" "Exported in place." 1

  run .vortex/tooling/src/export-db
  assert_success
  assert_output_contains "Started database export."
  assert_output_contains "Exported in place."
  assert_output_contains "Finished database export."

  assert_equal "1" "$(mock_get_call_num "${mock_export_db_file}")"
  assert_equal "0" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "export-db: Exports as a file through Docker Compose on the host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export RUN_ON_HOST=1

  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Exported through Docker Compose." 1

  run .vortex/tooling/src/export-db
  assert_success
  assert_output_contains "Exported through Docker Compose."
  assert_output_contains "Finished database export."

  assert_equal "1" "$(mock_get_call_num "${mock_docker}")"
  assert_equal "compose exec -T cli ./vendor/drevops/vortex-tooling/src/export-db-file" "$(mock_get_call_args "${mock_docker}" 1)"

  popd >/dev/null
}

@test "export-db: Detects the host from the Docker CLI when the override is unset" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Exported through detected Docker." 1

  run .vortex/tooling/src/export-db
  assert_success
  assert_output_contains "Exported through detected Docker."

  assert_equal "1" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "export-db: Exports as a container image on the host when an image name is set" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export RUN_ON_HOST=1
  export VORTEX_EXPORT_DB_IMAGE="myorg/myapp"

  mock_export_db_image=$(mock_command ".vortex/tooling/src/export-db-image")
  mock_set_output "${mock_export_db_image}" "Exported as a container image." 1
  mock_deploy=$(mock_command ".vortex/tooling/src/deploy-container-registry")

  run .vortex/tooling/src/export-db
  assert_success
  assert_output_contains "Exported as a container image."
  assert_output_contains "Finished database export."

  assert_equal "1" "$(mock_get_call_num "${mock_export_db_image}")"
  assert_equal "0" "$(mock_get_call_num "${mock_deploy}")"

  popd >/dev/null
}

@test "export-db: Deploys the container image when deployment is requested" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export RUN_ON_HOST=1
  export VORTEX_EXPORT_DB_IMAGE="myorg/myapp"
  export VORTEX_EXPORT_DB_CONTAINER_REGISTRY_DEPLOY_PROCEED=1

  mock_export_db_image=$(mock_command ".vortex/tooling/src/export-db-image")
  mock_set_output "${mock_export_db_image}" "Exported as a container image." 1
  mock_deploy=$(mock_command ".vortex/tooling/src/deploy-container-registry")
  mock_set_output "${mock_deploy}" "Deployed the container image." 1

  run .vortex/tooling/src/export-db
  assert_success
  assert_output_contains "Exported as a container image."
  assert_output_contains "Deployed the container image."

  assert_equal "1" "$(mock_get_call_num "${mock_export_db_image}")"
  assert_equal "1" "$(mock_get_call_num "${mock_deploy}")"

  popd >/dev/null
}
