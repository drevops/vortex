#!/usr/bin/env bats
#
# Test demo installation.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper
load _helper_drevops

@test "Demo auto discovery - enabled" {
  enable_demo_db

  assert_file_not_exists .data/db.sql

  run_install_quiet
  assert_files_present
  assert_git_repo

  assert_file_contains ".env" "CURL_DB_URL"
  assert_file_exists .data/db.sql
}

@test "Demo auto discovery - disabled" {
  enable_demo_db

  mktouch .data/db.sql
  assert_file_exists .data/db.sql

  run_install_quiet
  assert_files_present
  assert_git_repo

  assert_file_not_contains ".env" "CURL_DB_URL=http"
  assert_file_exists .data/db.sql

  contents=$(cat .data/db.sql)
  assert_empty "${contents}"
}

@test "Demo force disabled" {
  enable_demo_db
  export DREVOPS_DEMO=0

  assert_file_not_exists .data/db.sql

  run_install_quiet
  assert_files_present
  assert_git_repo

  assert_file_not_contains ".env" "CURL_DB_URL=http"
  assert_file_not_exists .data/db.sql
}

@test "Demo force enabled" {
  enable_demo_db
  export DREVOPS_DEMO=1

  mktouch .data/db.sql
  assert_file_exists .data/db.sql

  run_install_quiet
  assert_files_present
  assert_git_repo

  assert_file_contains ".env" "CURL_DB_URL=http"
  assert_file_exists .data/db.sql

  contents=$(cat .data/db.sql)
  assert_not_empty "${contents}"
}

@test "Demo auto discovery - enabled; skip demo processing" {
  enable_demo_db
  echo "DREVOPS_SKIP_DEMO=1">> .env

  assert_file_not_exists .data/db.sql

  run_install_quiet
  assert_files_present
  assert_git_repo

  assert_file_contains ".env" "CURL_DB_URL=http"
  assert_file_not_exists .data/db.sql
}
