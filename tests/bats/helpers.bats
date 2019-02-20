#!/usr/bin/env bats
#
# Tests for Bats helpers.
#
# Each assertion tests positive and negative behaviour.
#

load test_helper
load test_helper_drupaldev

@test "assert_success" {
  status=0
  assert_success

  status=1
  run assert_success
  [ "$status" -eq 1 ]
}

@test "assert_failure" {
  status=1
  assert_failure

  status=0
  run assert_failure
  [ "$status" -eq 1 ]
}

@test "assert_output" {
  output="output needle"
  assert_output "output needle"

  output="output not needle"
  run assert_output "output needle"
  assert_failure
}

@test "assert_output_contains" {
  run echo "some existing text"
  assert_output_contains "some existing text"

  run echo "some existing text"
  assert_output_contains "existing"

  run assert_output_contains "non-existing"
  assert_failure
}

@test "assert_output_not_contains" {
  run echo "some existing text"
  assert_output_not_contains "non-existing"

  run assert_output_not_contains "some existing text"
  assert_failure

  run assert_output_not_contains "existing"
  assert_failure
}

@test "assert_equal" {
  assert_equal 1 1

  run assert_equal 1 2
  assert_failure
}

@test "assert_empty" {
  assert_empty ""

  run assert_empty "something"
  assert_failure
}

@test "assert_not_empty" {
  assert_not_empty "something"

  run assert_not_empty ""
  assert_failure
}

@test "assert_contains" {
  assert_contains "needle" "some needle in a haystack"
  assert_contains "n[ee]dle" "some n[ee]dle in a haystack"

  run assert_contains "needle" "some ne edle in a haystack"
  assert_failure
}

@test "assert_not_contains" {
  assert_not_contains "otherneedle" "some needle in a haystack"
  assert_not_contains "othern[ee]dle" "some n[ee]dle in a haystack"

  run assert_not_contains "needle" "some needle in a haystack"
  assert_failure
  run assert_not_contains "n[ee]dle" "some n[ee]dle in a haystack"
  assert_failure
}

@test "assert_file_exists" {
  assert_file_exists "${BATS_TEST_FILENAME}"

  run assert_file_exists "some_file.txt"
  assert_failure
}

@test "assert_file_not_exists" {
  assert_file_not_exists "some_file.txt"

  run assert_file_not_exists "${BATS_TEST_FILENAME}"
  assert_failure
}

@test "assert_dir_exists" {
  assert_dir_exists "${BATS_TEST_DIRNAME}"

  run assert_dir_exists "some dir"
  assert_failure
}

@test "assert_dir_not_exists" {
  assert_dir_not_exists "some dir"

  run assert_dir_not_exists "${BATS_TEST_DIRNAME}"
  assert_failure
}

@test "assert_symlink_exists" {
  prepare_fixture_dir "${BATS_TMPDIR}/fixture_symlink"

  # Assert file.
  echo "some existing text" > "${BATS_TMPDIR}/fixture_symlink/src.txt"
  ln -s "${BATS_TMPDIR}/fixture_symlink/src.txt" "${BATS_TMPDIR}/fixture_symlink/dst.txt"
  assert_symlink_exists "${BATS_TMPDIR}/fixture_symlink/dst.txt"

  run assert_symlink_exists "${BATS_TMPDIR}/fixture_symlink/not-existing.txt"
  assert_failure

  # Assert dir.
  mkdir "${BATS_TMPDIR}/fixture_symlink/symlink_src"
  ln -s "${BATS_TMPDIR}/fixture_symlink/symlink_src" "${BATS_TMPDIR}/fixture_symlink/symlink_dst"
  assert_symlink_exists "${BATS_TMPDIR}/fixture_symlink/symlink_dst"
  run assert_symlink_exists "${BATS_TMPDIR}/fixture_symlink/symlink_dst_not_exisitng"
  assert_failure
}

@test "assert_symlink_not_exists" {
  prepare_fixture_dir "${BATS_TMPDIR}/fixture_symlink"

  echo "some existing text" > "${BATS_TMPDIR}/fixture_symlink/src.txt"
  ln -s "${BATS_TMPDIR}/fixture_symlink/src.txt" "${BATS_TMPDIR}/fixture_symlink/dst.txt"

  # Assert others.
  assert_symlink_not_exists "${BATS_TMPDIR}/fixture_symlink/src.txt"
  assert_symlink_not_exists "${BATS_TMPDIR}/fixture_symlink/other_dst.txt"
  assert_symlink_not_exists "${BATS_TMPDIR}/fixture_symlink/some_dir"

  run assert_symlink_not_exists "${BATS_TMPDIR}/fixture_symlink/dst.txt"
  assert_failure
}

@test "assert_file_mode" {
  prepare_fixture_dir "${BATS_TMPDIR}/fixture_mode"
  echo "some existing text" > "${BATS_TMPDIR}/fixture_mode/1.txt"
  chmod 644 "${BATS_TMPDIR}/fixture_mode/1.txt"
  assert_file_mode "${BATS_TMPDIR}/fixture_mode/1.txt" "644"
  chmod 664 "${BATS_TMPDIR}/fixture_mode/1.txt"
  assert_file_mode "${BATS_TMPDIR}/fixture_mode/1.txt" "644"
  chmod 755 "${BATS_TMPDIR}/fixture_mode/1.txt"
  assert_file_mode "${BATS_TMPDIR}/fixture_mode/1.txt" "755"
  chmod 775 "${BATS_TMPDIR}/fixture_mode/1.txt"
  assert_file_mode "${BATS_TMPDIR}/fixture_mode/1.txt" "755"
  chmod 777 "${BATS_TMPDIR}/fixture_mode/1.txt"
  assert_file_mode "${BATS_TMPDIR}/fixture_mode/1.txt" "755"

  run assert_file_mode "${BATS_TMPDIR}/fixture_mode/1.txt" "644"
  assert_failure
}

@test "assert_file_contains" {
  prepare_fixture_dir "${BATS_TMPDIR}/fixture_file_assert"
  echo "some existing text" >> "${BATS_TMPDIR}/fixture_file_assert/1.txt"
  echo "other existing text" >> "${BATS_TMPDIR}/fixture_file_assert/1.txt"
  echo "one more line of existing text" >> "${BATS_TMPDIR}/fixture_file_assert/1.txt"

  assert_file_contains "${BATS_TMPDIR}/fixture_file_assert/1.txt" "some existing text"

  run assert_file_contains "${BATS_TMPDIR}/fixture_file_assert/1.txt" "other non-existing text"
  assert_failure
}

@test "assert_file_not_contains" {
  prepare_fixture_dir "${BATS_TMPDIR}/fixture_file_assert"
  echo "some existing text" >> "${BATS_TMPDIR}/fixture_file_assert/1.txt"
  echo "other existing text" >> "${BATS_TMPDIR}/fixture_file_assert/1.txt"
  echo "one more line of existing text" >> "${BATS_TMPDIR}/fixture_file_assert/1.txt"

  assert_file_not_contains "${BATS_TMPDIR}/fixture_file_assert/1.txt" "other non-existing text"

  run assert_file_not_contains "${BATS_TMPDIR}/fixture_file_assert/1.txt" "some existing text"
  assert_failure

  # Text exists, non-existing file.
  assert_file_not_contains "${BATS_TMPDIR}/fixture_file_assert/somefile.txt" "some existing text"
}

@test "assert_dir_empty" {
  prepare_fixture_dir "${BATS_TMPDIR}/fixture/dir1"
  prepare_fixture_dir "${BATS_TMPDIR}/fixture/dir2"
  echo "some existing text" > "${BATS_TMPDIR}/fixture/dir2/1.txt"

  assert_dir_empty "${BATS_TMPDIR}/fixture/dir1"

  run assert_dir_empty "${BATS_TMPDIR}/fixture/dir2"
  assert_failure

  run assert_dir_empty "${BATS_TMPDIR}/non_existing"
  assert_failure
}

@test "assert_dir_not_empty" {
  prepare_fixture_dir "${BATS_TMPDIR}/fixture/dir1"
  prepare_fixture_dir "${BATS_TMPDIR}/fixture/dir2"
  echo "some existing text" > "${BATS_TMPDIR}/fixture/dir2/1.txt"

  assert_dir_not_empty "${BATS_TMPDIR}/fixture/dir2"

  run assert_dir_not_empty "${BATS_TMPDIR}/fixture/dir1"
  assert_failure

  run assert_dir_not_empty "${BATS_TMPDIR}/non_existing"
  assert_failure
}

@test "assert_dir_contains_string" {
  prepare_fixture_dir "${BATS_TMPDIR}/fixture"
  echo "some existing text" > "${BATS_TMPDIR}/fixture/1.txt"

  assert_dir_contains_string "${BATS_TMPDIR}/fixture" "existing"

  run assert_dir_contains_string "${BATS_TMPDIR}/fixture" "non-existing"
  assert_failure

  run assert_dir_contains_string "${BATS_TMPDIR}/non_existing"
  assert_failure
}

@test "assert_dir_not_contains_string" {
  prepare_fixture_dir "${BATS_TMPDIR}/fixture"
  echo "some existing text" > "${BATS_TMPDIR}/fixture/1.txt"
  echo "some other text" > "${BATS_TMPDIR}/fixture/2.txt"
  echo "some existing text" > "${BATS_TMPDIR}/fixture/3.txt"

  assert_dir_not_contains_string "${BATS_TMPDIR}/fixture" "non-existing"

  run assert_dir_not_contains_string "${BATS_TMPDIR}/fixture" "existing"
  assert_failure
  assert_output_contains "fixture/1.txt"
  assert_output_contains "fixture/3.txt"
  assert_output_not_contains "fixture/2.txt"

  # Non-existing dir.
  assert_dir_not_contains_string "${BATS_TMPDIR}/non_existing" "existing"
}

@test "assert_git_repo" {
  prepare_fixture_dir "${BATS_TMPDIR}/fixture/git_repo"
  prepare_fixture_dir "${BATS_TMPDIR}/fixture/not_git_repo"
  git --work-tree="${BATS_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TMPDIR}/fixture/git_repo/.git" init > /dev/null

  assert_git_repo "${BATS_TMPDIR}/fixture/git_repo"

  run assert_git_repo "${BATS_TMPDIR}/fixture/not_git_repo"
  assert_failure

  run assert_git_repo "${BATS_TMPDIR}/fixture/some_dir"
  assert_failure
}

@test "assert_not_git_repo" {
  prepare_fixture_dir "${BATS_TMPDIR}/fixture/git_repo"
  prepare_fixture_dir "${BATS_TMPDIR}/fixture/not_git_repo"
  git --work-tree="${BATS_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TMPDIR}/fixture/git_repo/.git" init > /dev/null

  assert_not_git_repo "${BATS_TMPDIR}/fixture/not_git_repo"

  run assert_not_git_repo "${BATS_TMPDIR}/fixture/git_repo"
  assert_failure

  run assert_not_git_repo "${BATS_TMPDIR}/fixture/some_dir"
  assert_failure
}

@test "assert_files_equal" {
  cp "${BATS_TEST_DIRNAME}/fixture.png" "${BATS_TMPDIR}/fixture1.png"
  echo "some other file" > "${BATS_TMPDIR}/fixture2.png"

  assert_files_equal "${BATS_TMPDIR}/fixture1.png" "${BATS_TMPDIR}/fixture1.png"

  run assert_files_equal "${BATS_TMPDIR}/fixture1.png" "${BATS_TMPDIR}/fixture2.png"
  assert_failure

  run assert_files_equal "${BATS_TMPDIR}/fixture3.png" "${BATS_TMPDIR}/fixture4.png"
  assert_failure

  run assert_files_equal "${BATS_TMPDIR}/fixture1.png" "${BATS_TMPDIR}/fixture3.png"
  assert_failure
}

@test "assert_files_not_equal" {
  cp "${BATS_TEST_DIRNAME}/fixture.png" "${BATS_TMPDIR}/fixture1.png"
  echo "some other file" > "${BATS_TMPDIR}/fixture2.png"

  assert_files_not_equal "${BATS_TMPDIR}/fixture1.png" "${BATS_TMPDIR}/fixture2.png"

  run assert_files_not_equal "${BATS_TMPDIR}/fixture1.png" "${BATS_TMPDIR}/fixture1.png"
  assert_failure

  run assert_files_not_equal "${BATS_TMPDIR}/fixture3.png" "${BATS_TMPDIR}/fixture1.png"
  assert_failure

  run assert_files_not_equal "${BATS_TMPDIR}/fixture1.png" "${BATS_TMPDIR}/fixture3.png"
  assert_failure
}

@test "Variables" {
  assert_contains "drupal-dev-bats" "${BUILD_DIR}"
}
