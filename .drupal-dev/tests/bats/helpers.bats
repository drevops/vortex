#!/usr/bin/env bats
#
# Tests for Bats helpers.
#

load test_helper
load test_helper_drupaldev

# Testing test system itself.
@test "Assertions" {
  status=0
  assert_success

  status=1
  assert_failure

  output="output needle"
  assert_output "output needle"

  assert_equal 1 1

  assert_empty ""
  assert_not_empty "something"

  assert_contains "needle" "some needle in a haystack"
  assert_contains "n[ee]dle" "some n[ee]dle in a haystack"
  assert_not_contains "otherneedle" "some needle in a haystack"
  assert_not_contains "othern[ee]dle" "some n[ee]dle in a haystack"

  assert_file_exists "${BATS_TEST_DIRNAME}/test_helper.bash"
  assert_file_not_exists "some_file.txt"

  assert_dir_exists "${BATS_TEST_DIRNAME}"
  assert_dir_not_exists "some dir"

  prepare_fixture_dir "${BATS_TMPDIR}/fixture_symlink"
  echo "some existing text" > "${BATS_TMPDIR}/fixture_symlink/1.txt"
  ln -s "${BATS_TMPDIR}/fixture_symlink/1.txt" "${BATS_TMPDIR}/fixture_symlink/2.txt"
  assert_symlink_exists "${BATS_TMPDIR}/fixture_symlink/2.txt"
  assert_symlink_not_exists "${BATS_TMPDIR}/fixture_symlink/1.txt"
  assert_symlink_not_exists "${BATS_TMPDIR}/fixture_symlink/3.txt"

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

  prepare_fixture_dir "${BATS_TMPDIR}/fixture_file_assert"
  echo "some existing text" >> "${BATS_TMPDIR}/fixture_file_assert/1.txt"
  echo "other existing text" >> "${BATS_TMPDIR}/fixture_file_assert/1.txt"
  echo "one more line of existing text" >> "${BATS_TMPDIR}/fixture_file_assert/1.txt"

  assert_file_contains "${BATS_TMPDIR}/fixture_file_assert/1.txt" "some existing text"
  assert_file_not_contains "${BATS_TMPDIR}/fixture_file_assert/1.txt" "other non-existing text"

  prepare_fixture_dir "${BATS_TMPDIR}/fixture"
  echo "some existing text" > "${BATS_TMPDIR}/fixture/1.txt"
  assert_dir_contains_string "${BATS_TMPDIR}/fixture" "existing"
  assert_dir_not_contains_string "${BATS_TMPDIR}/fixture" "non-existing"
}

@test "Variables" {
  assert_contains "drupal-dev-bats" "${BUILD_DIR}"
}
