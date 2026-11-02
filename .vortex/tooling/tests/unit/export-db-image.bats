#!/usr/bin/env bats
##
# Unit tests for the 'vortex-export-db-image' script.
#
# shellcheck disable=SC2030,SC2031

load ../_helper.bash

@test "export-db-image: Commits the service and saves the image archive" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -f .data/db.tar

  export VORTEX_EXPORT_DB_IMAGE="myorg/db"
  export VORTEX_EXPORT_DB_SERVICE_NAME="database2"
  export VORTEX_EXPORT_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_EXPORT_DB_IMAGE_DIR=".data"

  mock_command "sleep" >/dev/null
  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "abc123" 1
  mock_set_output "${mock_docker}" "Locked the tables." 2
  mock_set_output "${mock_docker}" "Unlocked the tables." 3
  mock_set_output "${mock_docker}" "Ran the upgrade." 4
  mock_set_output "${mock_docker}" "Locked the tables." 5
  mock_set_output "${mock_docker}" "sha256:def456" 6
  mock_set_side_effect "${mock_docker}" "mkdir -p .data && echo archive >.data/db.tar" 7

  run .vortex/tooling/src/vortex-export-db-image db.sql
  assert_success
  assert_output_contains "Found database2 service container with id abc123."
  assert_output_contains "[ OK ] Ran forced service upgrade."
  assert_output_contains "Committed exported container image with id def456."
  assert_output_contains "[ OK ] Exported database image saved to archive file .data/db.tar."
  assert_output_contains "[ OK ] Finished database data container image export."

  assert_string_contains "$(mock_get_call_args "${mock_docker}" 1)" "compose ps -q database2"
  # MySQL images have no 'mariadb-upgrade', so the command is checked for first.
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 4)" "compose exec -T database2 sh -c if command -v mariadb-upgrade >/dev/null 2>&1; then mariadb-upgrade --force || mariadb-upgrade --force; fi"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 6)" "commit abc123 registry.example.com/myorg/db"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 7)" "save -o .data/db.tar registry.example.com/myorg/db"
  assert_equal "7" "$(mock_get_call_num "${mock_docker}")"

  rm -f .data/db.tar

  popd >/dev/null
}

@test "export-db-image: Fails without a destination image" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_EXPORT_DB_IMAGE
  export VORTEX_EXPORT_DB_IMAGE_DIR=".data"

  mock_docker=$(mock_command "docker")

  run .vortex/tooling/src/vortex-export-db-image db.sql
  assert_failure
  assert_output_contains "[FAIL] Destination image name is not specified."

  assert_equal "0" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}
