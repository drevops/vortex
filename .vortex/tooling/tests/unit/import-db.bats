#!/usr/bin/env bats
##
# Unit tests for import-db router script.
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

@test "import-db: Imports a file in place when not on the host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export RUN_ON_HOST=0

  mock_docker=$(mock_command "docker")
  stub_sibling "import-db-file" "Imported in place."

  run .vortex/tooling/src/vortex-import-db
  assert_success
  assert_output_contains "Started database import."
  assert_output_contains "Imported in place."
  assert_output_contains "Finished database import."
  assert_equal "0" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "import-db: Imports a file through Docker Compose on the host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export RUN_ON_HOST=1

  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Imported through Docker Compose." 1

  run .vortex/tooling/src/vortex-import-db
  assert_success
  assert_output_contains "Imported through Docker Compose."
  assert_output_contains "Finished database import."
  assert_equal "1" "$(mock_get_call_num "${mock_docker}")"
  assert_equal "compose exec -T cli ./vendor/drevops/vortex-tooling/src/vortex-import-db-file" "$(mock_get_call_args "${mock_docker}" 1)"

  popd >/dev/null
}

@test "import-db: Detects the host from the Docker CLI when the override is unset" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Imported through detected Docker." 1

  run .vortex/tooling/src/vortex-import-db
  assert_success
  assert_output_contains "Imported through detected Docker."
  assert_equal "1" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "import-db: Passes the dump file argument through to the worker on the host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export RUN_ON_HOST=1

  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Imported through Docker Compose." 1

  run .vortex/tooling/src/vortex-import-db .data/db_custom.sql
  assert_success
  assert_equal "1" "$(mock_get_call_num "${mock_docker}")"
  assert_equal "compose exec -T cli ./vendor/drevops/vortex-tooling/src/vortex-import-db-file .data/db_custom.sql" "$(mock_get_call_args "${mock_docker}" 1)"

  popd >/dev/null
}
