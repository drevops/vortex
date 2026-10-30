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
  mock_set_output "${mock_docker}" "Copied the dump file back." 5

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
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 5)" "compose --progress quiet cp -L cli:/app/.data/db.sql ./.data/db.sql"
  assert_equal "5" "$(mock_get_call_num "${mock_docker}")"
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

@test "refresh-db: Fails when the refresh source fetches the dump file to another path" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -f .data/db.sql

  export VORTEX_DB_IMAGE="myorg/db"
  export VORTEX_DB_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB_SOURCE="container_registry"
  export VORTEX_REFRESH_DB_SOURCE="url"

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."
  stub_sibling "vortex-fetch-db" "Fetched the dump file elsewhere."
  stub_sibling "vortex-fetch-db-container-registry" 'Tagged base image ${VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE_BASE}.'

  mock_docker=$(mock_command "docker")

  run .vortex/tooling/src/vortex-refresh-db
  assert_failure
  assert_output_contains "Fetched the dump file elsewhere."
  assert_output_contains "[FAIL] The database dump file was not fetched to ./.data/db.sql. Remove the output directory and file name overrides of the database fetch source."
  assert_output_not_contains "Tagged base image"

  assert_equal "0" "$(mock_get_call_num "${mock_docker}")"

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

@test "refresh-db: Fails when the exported dump file cannot be copied from the cli container" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_DB_IMAGE VORTEX_DB_IMAGE_BASE
  export VORTEX_FETCH_DB_SOURCE="url"

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."
  stub_sibling "vortex-export-db" 'Exported the database to ${1}.'

  mock_command "sleep" >/dev/null
  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Started the stack." 1
  mock_set_output "${mock_docker}" "Provisioned the site." 2
  mock_set_status "${mock_docker}" 1 3

  run .vortex/tooling/src/vortex-refresh-db
  assert_failure
  assert_output_contains "Exported the database to db.sql."
  assert_output_contains "[FAIL] Unable to copy database dump file ./.data/db.sql from the cli container."
  assert_output_not_contains "Finished database refresh."

  popd >/dev/null
}

@test "refresh-db: Skips an indexed database refresh when it is not enabled" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DB_INDEX="2"
  export VORTEX_DB2_IMAGE="myorg/migration-db"
  export VORTEX_DB_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  unset VORTEX_REFRESH_DB2_PROCEED

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."

  mock_docker=$(mock_command "docker")

  run .vortex/tooling/src/vortex-refresh-db
  assert_success
  assert_output_contains "[INFO] Started database 2 refresh."
  assert_output_contains "[ OK ] Skipped database 2 refresh as VORTEX_REFRESH_DB2_PROCEED is not set to 1."
  assert_output_not_contains "Logged in to the registry."

  assert_equal "0" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "refresh-db: Skips an indexed database refresh without a base image" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DB_INDEX="2"
  export VORTEX_DB2_IMAGE="myorg/migration-db"
  export VORTEX_REFRESH_DB2_PROCEED=1
  unset VORTEX_DB_IMAGE_BASE VORTEX_DB2_IMAGE_BASE

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."

  mock_docker=$(mock_command "docker")

  run .vortex/tooling/src/vortex-refresh-db
  assert_success
  assert_output_contains "[ OK ] Skipped database 2 refresh as no base image is set in VORTEX_DB2_IMAGE_BASE or VORTEX_DB_IMAGE_BASE."
  assert_output_not_contains "Logged in to the registry."

  assert_equal "0" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "refresh-db: Fails an indexed database refresh without an image" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DB_INDEX="2"
  export VORTEX_REFRESH_DB2_PROCEED=1
  export VORTEX_DB_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  unset VORTEX_DB2_IMAGE

  mock_docker=$(mock_command "docker")

  run .vortex/tooling/src/vortex-refresh-db
  assert_failure
  assert_output_contains "[FAIL] The database 2 image is not set. Set VORTEX_DB2_IMAGE to the image to refresh."

  assert_equal "0" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "refresh-db: Imports the migration dump file into the base image and pushes the migration image" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  touch .data/db2.sql

  export VORTEX_DB_INDEX="2"
  export VORTEX_DB2_IMAGE="myorg/migration-db"
  export VORTEX_REFRESH_DB2_PROCEED=1
  export VORTEX_DB_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB2_SOURCE="url"
  unset VORTEX_DB2_IMAGE_BASE

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."
  stub_sibling "vortex-fetch-db" "Fetched the dump file."
  stub_sibling "vortex-fetch-db-container-registry" 'Tagged base image ${VORTEX_FETCH_DB2_CONTAINER_REGISTRY_IMAGE_BASE} for index ${VORTEX_DB_INDEX}.'
  stub_sibling "vortex-export-db-image" 'Exported service ${VORTEX_EXPORT_DB_SERVICE_NAME} as ${VORTEX_EXPORT_DB_IMAGE} to ${1}.'
  stub_sibling "vortex-push-db-image" 'Pushed database ${VORTEX_DB_INDEX} image.'
  stub_sibling "vortex-export-db" "Exported the primary database."

  mock_sleep=$(mock_command "sleep")
  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Started the database2 service." 1
  mock_set_output "${mock_docker}" "Imported the dump file." 2

  run .vortex/tooling/src/vortex-refresh-db
  assert_success
  assert_output_contains "[INFO] Started database 2 refresh."
  # A file source is fetched by the migration fetch step, not by the refresh.
  assert_output_not_contains "Fetched the dump file."
  assert_output_contains "Tagged base image drevops/mariadb-drupal-data:26.10.0 for index 2."
  assert_output_contains "[ OK ] Imported database dump file ./.data/db2.sql into the database2 service on the drevops/mariadb-drupal-data:26.10.0 base image."
  assert_output_contains "Exported service database2 as myorg/migration-db to db2.sql."
  assert_output_contains "Pushed database 2 image."
  assert_output_not_contains "Exported the primary database."
  assert_output_contains "[ OK ] Finished database 2 refresh."

  assert_string_contains "$(mock_get_call_args "${mock_docker}" 1)" "compose up --detach --force-recreate database2"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 2)" "compose exec -T database2 sh -c"
  assert_equal "2" "$(mock_get_call_num "${mock_docker}")"
  assert_equal "1" "$(mock_get_call_num "${mock_sleep}")"

  popd >/dev/null
}

@test "refresh-db: Fetches the migration dump file from the refresh source for a container registry source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -f .data/db2.sql

  export VORTEX_DB_INDEX="2"
  export VORTEX_DB2_IMAGE="myorg/migration-db"
  export VORTEX_REFRESH_DB2_PROCEED=1
  export VORTEX_DB2_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB2_SOURCE="container_registry"
  export VORTEX_REFRESH_DB2_SOURCE="url"

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."
  printf '#!/usr/bin/env bash\nmkdir -p .data\ntouch .data/db2.sql\necho "Fetched index ${VORTEX_DB_INDEX} from ${VORTEX_FETCH_DB2_SOURCE} with force ${VORTEX_FETCH_DB2_FORCE}."\n' >.vortex/tooling/src/vortex-fetch-db
  chmod +x .vortex/tooling/src/vortex-fetch-db
  stub_sibling "vortex-fetch-db-container-registry" 'Tagged base image ${VORTEX_FETCH_DB2_CONTAINER_REGISTRY_IMAGE_BASE}.'
  stub_sibling "vortex-export-db-image" 'Exported service ${VORTEX_EXPORT_DB_SERVICE_NAME}.'
  stub_sibling "vortex-push-db-image" 'Pushed database ${VORTEX_DB_INDEX} image.'

  mock_command "sleep" >/dev/null
  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Started the database2 service." 1
  mock_set_output "${mock_docker}" "Imported the dump file." 2

  run .vortex/tooling/src/vortex-refresh-db
  assert_success
  assert_output_contains "Fetched index 2 from url with force 1."
  assert_output_contains "Tagged base image drevops/mariadb-drupal-data:26.10.0."
  assert_output_contains "Exported service database2."
  assert_output_contains "Pushed database 2 image."
  assert_output_contains "[ OK ] Finished database 2 refresh."

  assert_equal "2" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "refresh-db: Fails when the migration dump file cannot be imported" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  touch .data/db2.sql

  export VORTEX_DB_INDEX="2"
  export VORTEX_DB2_IMAGE="myorg/migration-db"
  export VORTEX_REFRESH_DB2_PROCEED=1
  export VORTEX_DB_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB2_SOURCE="url"

  stub_sibling "vortex-login-container-registry" "Logged in to the registry."
  stub_sibling "vortex-fetch-db-container-registry" "Tagged the base image."
  stub_sibling "vortex-export-db-image" "Exported the migration image."
  stub_sibling "vortex-push-db-image" "Pushed the migration image."

  mock_command "sleep" >/dev/null
  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Started the database2 service." 1
  mock_set_status "${mock_docker}" 1 2

  run .vortex/tooling/src/vortex-refresh-db
  assert_failure
  assert_output_contains "[FAIL] Unable to import database dump file ./.data/db2.sql into the database2 service."
  assert_output_not_contains "Exported the migration image."
  assert_output_not_contains "Pushed the migration image."

  popd >/dev/null
}
