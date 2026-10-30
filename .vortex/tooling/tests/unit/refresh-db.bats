#!/usr/bin/env bats
##
# Unit tests for the 'vortex-refresh-db' script.
#
# The stub markers use single quotes on purpose: the `${...}` must reach the
# generated stub literally and expand when the stub runs, not when the test
# calls stub_sibling - so SC2016 is suppressed here.
#
# shellcheck disable=SC2016,SC2030,SC2031

load ../_helper.bash

@test "refresh-db: Provisions from the dump file and exports the dump file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  touch .data/db.sql

  unset VORTEX_DB_IMAGE VORTEX_DB_IMAGE_BASE
  export VORTEX_FETCH_DB_SOURCE="url"

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."
  stub_sibling "vortex-fetch-db-container-registry" "Fetched the base image."
  stub_sibling "vortex-export-db" 'Exported the database to ${1}.'

  mock_sleep=$(mock_command "sleep")
  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Started the stack." 1
  mock_set_output "${mock_docker}" "Created the directory." 2
  mock_set_output "${mock_docker}" "Copied the dump file." 3
  mock_set_output "${mock_docker}" "Provisioned the site." 4
  mock_set_output "${mock_docker}" "The dump file exists." 5
  mock_set_output "${mock_docker}" "Copied the dump file back." 6

  run .vortex/tooling/src/vortex-refresh-db
  assert_success
  assert_output_contains "[INFO] Started database refresh."
  assert_output_contains "Logged in to the registry."
  assert_output_not_contains "Fetched the base image."
  assert_output_contains "[ OK ] Copied database dump file ./.data/db.sql into the cli container."
  assert_output_contains "Exported the database to db.sql."
  assert_output_contains "[ OK ] Copied database dump file ./.data/db.sql from the cli container."
  assert_output_contains "[ OK ] Finished database refresh."

  assert_string_contains "$(mock_get_call_args "${mock_docker}" 1)" "compose up --detach"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 3)" "compose --progress quiet cp -L ./.data/db.sql cli:/app/.data/db.sql"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 4)" "-e VORTEX_DB_IMAGE= -e VORTEX_PROVISION_POST_OPERATIONS_SKIP=1 -T cli ./vendor/bin/vortex-provision"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 5)" "compose exec -T cli test -f /app/.data/db.sql"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 6)" "compose --progress quiet cp -L cli:/app/.data/db.sql ./.data/db.sql"
  assert_equal "6" "$(mock_get_call_num "${mock_docker}")"
  assert_equal "1" "$(mock_get_call_num "${mock_sleep}")"

  assert_file_exists .data/db.sql

  popd >/dev/null
}

@test "refresh-db: Provisions from the fetched image and exports the image without a base image" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_DB_IMAGE_BASE
  export VORTEX_DB_IMAGE="myorg/db"
  export VORTEX_FETCH_DB_SOURCE="container_registry"

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."
  stub_sibling "vortex-fetch-db-container-registry" "Fetched the base image."
  stub_sibling "vortex-export-db" 'Exported the database to ${1}.'

  mock_command "sleep" >/dev/null
  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Started the stack." 1
  mock_set_output "${mock_docker}" "Provisioned the site." 2

  run .vortex/tooling/src/vortex-refresh-db
  assert_success
  assert_output_not_contains "Fetched the base image."
  assert_output_not_contains "Exporting the database from the myorg/db image"
  assert_output_not_contains "Copied database dump file"
  assert_output_contains "Exported the database to db.sql."
  assert_output_contains "[ OK ] Finished database refresh."

  # The fetched image holds the database, so the provisioning uses it.
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 2)" "-e VORTEX_DB_IMAGE=myorg/db -e VORTEX_PROVISION_POST_OPERATIONS_SKIP=1 -T cli ./vendor/bin/vortex-provision"
  assert_equal "2" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "refresh-db: Imports the fetched dump file into the base image" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  touch .data/db.sql

  export VORTEX_DB_IMAGE="myorg/db"
  export VORTEX_DB_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB_SOURCE="url"

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."
  stub_sibling "vortex-fetch-db-container-registry" 'Tagged base image ${VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE_BASE}.'
  stub_sibling "vortex-export-db" 'Exported the database to ${1}.'

  mock_command "sleep" >/dev/null
  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Started the stack." 1
  mock_set_output "${mock_docker}" "Created the directory." 2
  mock_set_output "${mock_docker}" "Copied the dump file." 3
  mock_set_output "${mock_docker}" "Provisioned the site." 4

  run .vortex/tooling/src/vortex-refresh-db
  assert_success
  assert_output_contains "Tagged base image drevops/mariadb-drupal-data:26.10.0."
  assert_output_not_contains "Exporting the database from the myorg/db image"
  assert_output_not_contains "Rebuilding the database service"
  assert_output_contains "[ OK ] Copied database dump file ./.data/db.sql into the cli container."
  assert_output_contains "Exported the database to db.sql."
  assert_output_contains "[ OK ] Finished database refresh."

  # The base image holds no database, so the provisioning imports the dump.
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 4)" "-e VORTEX_DB_IMAGE= -e VORTEX_PROVISION_POST_OPERATIONS_SKIP=1 -T cli ./vendor/bin/vortex-provision"
  assert_equal "4" "$(mock_get_call_num "${mock_docker}")"

  # Database-in-image storage exports the image archive instead of the dump.
  assert_file_not_exists .data/db.sql

  popd >/dev/null
}

@test "refresh-db: Imports a dump file from the refresh source into the base image for a container registry source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DB_IMAGE="myorg/db"
  export VORTEX_DB_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB_SOURCE="container_registry"
  export VORTEX_REFRESH_DB_SOURCE="url"
  touch "${BUILD_DIR}/bash_env"
  export BASH_ENV="${BUILD_DIR}/bash_env"

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."
  stub_sibling "vortex-fetch-db-container-registry" 'Tagged base image ${VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE_BASE}.'
  stub_sibling "vortex-export-db" 'Exported the database to ${1}.'
  printf '#!/usr/bin/env bash\nmkdir -p .data\ntouch .data/db.sql\necho "Fetched from ${VORTEX_FETCH_DB_SOURCE} with force ${VORTEX_FETCH_DB_FORCE} and BASH_ENV [${BASH_ENV}]."\n' >.vortex/tooling/src/vortex-fetch-db
  chmod +x .vortex/tooling/src/vortex-fetch-db

  mock_sleep=$(mock_command "sleep")
  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Started the stack." 1
  mock_set_output "${mock_docker}" "Created the directory." 2
  mock_set_output "${mock_docker}" "Copied the dump file." 3
  mock_set_output "${mock_docker}" "Provisioned the site." 4

  run .vortex/tooling/src/vortex-refresh-db
  assert_success
  # The dump file comes from the refresh source, never from the fetched image.
  assert_output_contains "Fetched from url with force 1 and BASH_ENV []."
  assert_output_contains "Tagged base image drevops/mariadb-drupal-data:26.10.0."
  assert_output_contains "[ OK ] Copied database dump file ./.data/db.sql into the cli container."
  assert_output_contains "Exported the database to db.sql."
  assert_output_contains "[ OK ] Finished database refresh."

  assert_string_contains "$(mock_get_call_args "${mock_docker}" 1)" "compose up --detach"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 3)" "compose --progress quiet cp -L ./.data/db.sql cli:/app/.data/db.sql"
  # The base image holds no database, so the provisioning imports the dump.
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 4)" "-e VORTEX_DB_IMAGE= -e VORTEX_PROVISION_POST_OPERATIONS_SKIP=1 -T cli ./vendor/bin/vortex-provision"
  assert_equal "4" "$(mock_get_call_num "${mock_docker}")"
  assert_equal "1" "$(mock_get_call_num "${mock_sleep}")"

  assert_file_not_exists .data/db.sql

  popd >/dev/null
}

@test "refresh-db: Fails without exporting when the provisioning fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_DB_IMAGE_BASE
  export VORTEX_DB_IMAGE="myorg/db"
  export VORTEX_FETCH_DB_SOURCE="container_registry"

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."
  stub_sibling "vortex-export-db" 'Exported the database to ${1}.'

  mock_command "sleep" >/dev/null
  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Started the stack." 1
  mock_set_status "${mock_docker}" 1 2

  run .vortex/tooling/src/vortex-refresh-db
  assert_failure
  assert_output_not_contains "Exported the database to db.sql."
  assert_output_not_contains "Finished database refresh."

  popd >/dev/null
}

@test "refresh-db: Fails without a dump file source for a container registry source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DB_IMAGE="myorg/db"
  export VORTEX_DB_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB_SOURCE="container_registry"

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."
  stub_sibling "vortex-fetch-db" "Fetched the dump file."
  stub_sibling "vortex-fetch-db-container-registry" 'Tagged base image ${VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE_BASE}.'

  mock_docker=$(mock_command "docker")

  for refresh_source in "" container_registry none; do
    export VORTEX_REFRESH_DB_SOURCE="${refresh_source}"

    run .vortex/tooling/src/vortex-refresh-db
    assert_failure
    assert_output_contains "[FAIL] The myorg/db image is built from a database dump file, but VORTEX_REFRESH_DB_SOURCE is \"${refresh_source}\". Set it to the source of the dump file: url, ftp, acquia, lagoon or s3."
    assert_output_not_contains "Logged in to the registry."
    assert_output_not_contains "Fetched the dump file."
  done

  assert_equal "0" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}
