#!/usr/bin/env bats
#
# Unit tests for the 'vortex-dbui' script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Sequel Ace opens the database when installed" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p "${BUILD_DIR}/Sequel Ace.app"
  export VORTEX_HOST_SEQUELACE_PATH="${BUILD_DIR}/Sequel Ace.app"
  unset VORTEX_DBUI_CLIENT
  unset VORTEX_DB_INDEX

  declare -a STEPS=(
    "@docker compose port database 3306 # 0.0.0.0:33060"
    "@open mysql://drupal:drupal@127.0.0.1:33060/drupal -a Sequel Ace # 0"
    "Opening the database in Sequel Ace."
    "Opened the database in Sequel Ace."
    "- phpMyAdmin"
  )

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-dbui
  assert_success
  steps_run "assert" "${mocks}"

  popd >/dev/null
}

@test "Sequel Ace opens the indexed database with its credentials" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p "${BUILD_DIR}/Sequel Ace.app"
  export VORTEX_HOST_SEQUELACE_PATH="${BUILD_DIR}/Sequel Ace.app"
  export VORTEX_DB_INDEX=2
  export DATABASE2_USERNAME="user2"
  export DATABASE2_PASSWORD="pass2"
  unset VORTEX_DBUI_CLIENT

  declare -a STEPS=(
    "@docker compose port database2 3306 # 0.0.0.0:33061"
    "@open mysql://user2:pass2@127.0.0.1:33061/drupal -a Sequel Ace # 0"
    "Opening the database 2 in Sequel Ace."
    "Opened the database 2 in Sequel Ace."
  )

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-dbui
  assert_success
  steps_run "assert" "${mocks}"

  popd >/dev/null
}

@test "phpMyAdmin URL is printed when Sequel Ace is not installed" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_HOST_SEQUELACE_PATH="${BUILD_DIR}/missing"
  unset VORTEX_DBUI_CLIENT
  unset VORTEX_DB_INDEX

  declare -a STEPS=(
    "@docker compose port dbui 80 # 127.0.0.1:8080"
    "Open the database in phpMyAdmin:"
    "http://127.0.0.1:8080/index.php?server=1"
  )

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-dbui
  assert_success
  steps_run "assert" "${mocks}"

  popd >/dev/null
}

@test "phpMyAdmin URL points to the indexed database server" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_HOST_SEQUELACE_PATH="${BUILD_DIR}/missing"
  export VORTEX_DB_INDEX=2
  unset VORTEX_DBUI_CLIENT

  declare -a STEPS=(
    "@docker compose port dbui 80 # 127.0.0.1:8080"
    "Open the database 2 in phpMyAdmin:"
    "http://127.0.0.1:8080/index.php?server=2"
  )

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-dbui
  assert_success
  steps_run "assert" "${mocks}"

  popd >/dev/null
}

@test "phpMyAdmin client skips an installed Sequel Ace" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p "${BUILD_DIR}/Sequel Ace.app"
  export VORTEX_HOST_SEQUELACE_PATH="${BUILD_DIR}/Sequel Ace.app"
  export VORTEX_DBUI_CLIENT="phpmyadmin"
  unset VORTEX_DB_INDEX

  declare -a STEPS=(
    "@docker compose port dbui 80 # 127.0.0.1:8080"
    "Open the database in phpMyAdmin:"
    "http://127.0.0.1:8080/index.php?server=1"
    "- Sequel Ace"
  )

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-dbui
  assert_success
  steps_run "assert" "${mocks}"

  popd >/dev/null
}

@test "phpMyAdmin client fails when the dbui container is not running" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DBUI_CLIENT="phpmyadmin"
  unset VORTEX_DB_INDEX

  declare -a STEPS=(
    "@docker compose port dbui 80 # 1"
    "The dbui container is not running."
  )

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-dbui
  assert_failure
  steps_run "assert" "${mocks}"

  popd >/dev/null
}

@test "Sequel Ace client fails when Sequel Ace is not installed" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_HOST_SEQUELACE_PATH="${BUILD_DIR}/missing"
  export VORTEX_DBUI_CLIENT="sequelace"
  unset VORTEX_DB_INDEX

  run .vortex/tooling/src/vortex-dbui
  assert_failure
  assert_output_contains "Sequel Ace is not installed."
  assert_output_not_contains "dbui"

  popd >/dev/null
}

@test "No client fails when Sequel Ace is not installed and dbui is not running" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_HOST_SEQUELACE_PATH="${BUILD_DIR}/missing"
  unset VORTEX_DBUI_CLIENT
  unset VORTEX_DB_INDEX

  declare -a STEPS=(
    "@docker compose port dbui 80 # 1"
    "Sequel Ace is not installed and the dbui container is not running."
  )

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-dbui
  assert_failure
  steps_run "assert" "${mocks}"

  popd >/dev/null
}
