#!/usr/bin/env bats
#
# Tests for Bats helpers.
#
# Each assertion tests positive and negative behaviour.
#
# shellcheck disable=SC2129

load _helper

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
  assert_output_contains "some EXISTING text"

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

  run assert_output_not_contains "some EXISTING text"
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

  mktouch "${BATS_TEST_TMPDIR}/file1.txt"
  mktouch "${BATS_TEST_TMPDIR}/file2.txt"
  mktouch "${BATS_TEST_TMPDIR}/file3.md"
  mktouch "${BATS_TEST_TMPDIR}/a.b.c.d.doc"

  assert_file_exists "${BATS_TEST_TMPDIR}/file1.txt"
  assert_file_exists "${BATS_TEST_TMPDIR}/file2.txt"
  assert_file_exists "${BATS_TEST_TMPDIR}/file3.md"

  assert_file_exists "${BATS_TEST_TMPDIR}/file*"
  assert_file_exists "${BATS_TEST_TMPDIR}/*.txt"
  assert_file_exists "${BATS_TEST_TMPDIR}/*.doc"

  run assert_file_exists "some_file.txt"
  assert_failure

  run assert_file_exists "${BATS_TEST_TMPDIR}/*.rtf"
  assert_failure

  run assert_file_exists "${BATS_TEST_TMPDIR}/other*"
  assert_failure
}

@test "assert_file_not_exists" {
  assert_file_not_exists "some_file.txt"

  mktouch "${BATS_TEST_TMPDIR}/file1.txt"
  mktouch "${BATS_TEST_TMPDIR}/file2.txt"
  mktouch "${BATS_TEST_TMPDIR}/file3.md"

  assert_file_not_exists "${BATS_TEST_TMPDIR}/otherfile1.txt"
  assert_file_not_exists "${BATS_TEST_TMPDIR}/otherfile*"
  assert_file_not_exists "${BATS_TEST_TMPDIR}/*.rtf"

  run assert_file_not_exists "${BATS_TEST_FILENAME}"
  assert_failure

  run assert_file_not_exists "${BATS_TEST_TMPDIR}/file1.txt"
  assert_failure

  run assert_file_not_exists "${BATS_TEST_TMPDIR}/file*"
  assert_failure

  run assert_file_not_exists "${BATS_TEST_TMPDIR}/*.txt"
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
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture_symlink"

  # Assert file.
  echo "some existing text" > "${BATS_TEST_TMPDIR}/fixture_symlink/src.txt"
  ln -s "${BATS_TEST_TMPDIR}/fixture_symlink/src.txt" "${BATS_TEST_TMPDIR}/fixture_symlink/dst.txt"
  assert_symlink_exists "${BATS_TEST_TMPDIR}/fixture_symlink/dst.txt"

  run assert_symlink_exists "${BATS_TEST_TMPDIR}/fixture_symlink/not-existing.txt"
  assert_failure

  # Assert dir.
  mkdir "${BATS_TEST_TMPDIR}/fixture_symlink/symlink_src"
  ln -s "${BATS_TEST_TMPDIR}/fixture_symlink/symlink_src" "${BATS_TEST_TMPDIR}/fixture_symlink/symlink_dst"
  assert_symlink_exists "${BATS_TEST_TMPDIR}/fixture_symlink/symlink_dst"
  run assert_symlink_exists "${BATS_TEST_TMPDIR}/fixture_symlink/symlink_dst_not_exisitng"
  assert_failure
}

@test "assert_symlink_not_exists" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture_symlink"

  echo "some existing text" > "${BATS_TEST_TMPDIR}/fixture_symlink/src.txt"
  ln -s "${BATS_TEST_TMPDIR}/fixture_symlink/src.txt" "${BATS_TEST_TMPDIR}/fixture_symlink/dst.txt"

  # Assert others.
  assert_symlink_not_exists "${BATS_TEST_TMPDIR}/fixture_symlink/src.txt"
  assert_symlink_not_exists "${BATS_TEST_TMPDIR}/fixture_symlink/other_dst.txt"
  assert_symlink_not_exists "${BATS_TEST_TMPDIR}/fixture_symlink/some_dir"

  run assert_symlink_not_exists "${BATS_TEST_TMPDIR}/fixture_symlink/dst.txt"
  assert_failure
}

@test "assert_file_mode" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture_mode"
  echo "some existing text" > "${BATS_TEST_TMPDIR}/fixture_mode/1.txt"
  chmod 644 "${BATS_TEST_TMPDIR}/fixture_mode/1.txt"
  assert_file_mode "${BATS_TEST_TMPDIR}/fixture_mode/1.txt" "644"
  chmod 664 "${BATS_TEST_TMPDIR}/fixture_mode/1.txt"
  assert_file_mode "${BATS_TEST_TMPDIR}/fixture_mode/1.txt" "644"
  chmod 755 "${BATS_TEST_TMPDIR}/fixture_mode/1.txt"
  assert_file_mode "${BATS_TEST_TMPDIR}/fixture_mode/1.txt" "755"
  chmod 775 "${BATS_TEST_TMPDIR}/fixture_mode/1.txt"
  assert_file_mode "${BATS_TEST_TMPDIR}/fixture_mode/1.txt" "755"
  chmod 777 "${BATS_TEST_TMPDIR}/fixture_mode/1.txt"
  assert_file_mode "${BATS_TEST_TMPDIR}/fixture_mode/1.txt" "755"

  run assert_file_mode "${BATS_TEST_TMPDIR}/fixture_mode/1.txt" "644"
  assert_failure
}

@test "assert_file_contains" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture_file_assert"
  echo "some existing text" >> "${BATS_TEST_TMPDIR}/fixture_file_assert/1.txt"
  echo "other existing text" >> "${BATS_TEST_TMPDIR}/fixture_file_assert/1.txt"
  echo "one more line of existing text" >> "${BATS_TEST_TMPDIR}/fixture_file_assert/1.txt"

  assert_file_contains "${BATS_TEST_TMPDIR}/fixture_file_assert/1.txt" "some existing text"

  run assert_file_contains "${BATS_TEST_TMPDIR}/fixture_file_assert/1.txt" "other non-existing text"
  assert_failure
}

@test "assert_file_not_contains" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture_file_assert"
  echo "some existing text" >> "${BATS_TEST_TMPDIR}/fixture_file_assert/1.txt"
  echo "other existing text" >> "${BATS_TEST_TMPDIR}/fixture_file_assert/1.txt"
  echo "one more line of existing text" >> "${BATS_TEST_TMPDIR}/fixture_file_assert/1.txt"

  assert_file_not_contains "${BATS_TEST_TMPDIR}/fixture_file_assert/1.txt" "other non-existing text"

  run assert_file_not_contains "${BATS_TEST_TMPDIR}/fixture_file_assert/1.txt" "some existing text"
  assert_failure

  # Text exists, non-existing file.
  assert_file_not_contains "${BATS_TEST_TMPDIR}/fixture_file_assert/somefile.txt" "some existing text"
}

@test "assert_dir_empty" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/dir1"
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/dir2"
  echo "some existing text" > "${BATS_TEST_TMPDIR}/fixture/dir2/1.txt"

  assert_dir_empty "${BATS_TEST_TMPDIR}/fixture/dir1"

  run assert_dir_empty "${BATS_TEST_TMPDIR}/fixture/dir2"
  assert_failure

  run assert_dir_empty "${BATS_TEST_TMPDIR}/non_existing"
  assert_failure
}

@test "assert_dir_not_empty" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/dir1"
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/dir2"
  echo "some existing text" > "${BATS_TEST_TMPDIR}/fixture/dir2/1.txt"

  assert_dir_not_empty "${BATS_TEST_TMPDIR}/fixture/dir2"

  run assert_dir_not_empty "${BATS_TEST_TMPDIR}/fixture/dir1"
  assert_failure

  run assert_dir_not_empty "${BATS_TEST_TMPDIR}/non_existing"
  assert_failure
}

@test "assert_dir_contains_string" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture"
  echo "some existing text" > "${BATS_TEST_TMPDIR}/fixture/1.txt"

  assert_dir_contains_string "${BATS_TEST_TMPDIR}/fixture" "existing"

  run assert_dir_contains_string "${BATS_TEST_TMPDIR}/fixture" "non-existing"
  assert_failure

  run assert_dir_contains_string "${BATS_TEST_TMPDIR}/non_existing"
  assert_failure

  # Excluded dir.
  rm "${BATS_TEST_TMPDIR}/fixture/1.txt" > /dev/null
  mkdir -p "${BATS_TEST_TMPDIR}/fixture/scripts/drevops"
  echo "some existing text" > "${BATS_TEST_TMPDIR}/fixture/scripts/drevops/2.txt"

  run assert_dir_contains_string "${BATS_TEST_TMPDIR}/fixture" "existing"
  assert_failure
}

@test "assert_dir_not_contains_string" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture"
  echo "some existing text" > "${BATS_TEST_TMPDIR}/fixture/1.txt"
  echo "some other text" > "${BATS_TEST_TMPDIR}/fixture/2.txt"
  echo "some existing text" > "${BATS_TEST_TMPDIR}/fixture/3.txt"

  assert_dir_not_contains_string "${BATS_TEST_TMPDIR}/fixture" "non-existing"

  run assert_dir_not_contains_string "${BATS_TEST_TMPDIR}/fixture" "existing"
  assert_failure
  assert_output_contains "fixture/1.txt"
  assert_output_contains "fixture/3.txt"
  assert_output_not_contains "fixture/2.txt"

  # Non-existing dir.
  assert_dir_not_contains_string "${BATS_TEST_TMPDIR}/non_existing" "existing"

  # Excluded dir.
  rm "${BATS_TEST_TMPDIR}/fixture/1.txt" > /dev/null
  mkdir -p "${BATS_TEST_TMPDIR}/fixture/scripts/drevops"
  echo "some existing text" > "${BATS_TEST_TMPDIR}/fixture/scripts/drevops/2.txt"

  assert_dir_contains_string "${BATS_TEST_TMPDIR}/fixture" "existing"
}

@test "assert_git_repo" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/git_repo"
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/git_repo_empty_dot_git"
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/not_git_repo"
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" init > /dev/null

  assert_git_repo "${BATS_TEST_TMPDIR}/fixture/git_repo"

  mkdir "${BATS_TEST_TMPDIR}/fixture/git_repo_empty_dot_git/.git"
  assert_dir_exists "${BATS_TEST_TMPDIR}/fixture/git_repo_empty_dot_git/.git"
  assert_file_not_exists "${BATS_TEST_TMPDIR}/fixture/git_repo_empty_dot_git/HEAD"
  run assert_git_repo "${BATS_TEST_TMPDIR}/fixture/git_repo_empty_dot_git"
  assert_failure

  run assert_git_repo "${BATS_TEST_TMPDIR}/fixture/not_git_repo"
  assert_failure

  run assert_git_repo "${BATS_TEST_TMPDIR}/fixture/some_dir"
  assert_failure
}

@test "assert_not_git_repo" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/git_repo"
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/not_git_repo"
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" init > /dev/null

  assert_not_git_repo "${BATS_TEST_TMPDIR}/fixture/not_git_repo"

  run assert_not_git_repo "${BATS_TEST_TMPDIR}/fixture/git_repo"
  assert_failure

  run assert_not_git_repo "${BATS_TEST_TMPDIR}/fixture/some_dir"
  assert_failure
}

@test "assert_git_clean" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/git_repo"
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" init > /dev/null
  assert_git_repo "${BATS_TEST_TMPDIR}/fixture/git_repo"

  assert_git_clean "${BATS_TEST_TMPDIR}/fixture/git_repo"

  mktouch "${BATS_TEST_TMPDIR}/fixture/git_repo/uncommitted_file"
  run assert_git_clean "${BATS_TEST_TMPDIR}/fixture/git_repo"
  assert_failure

  # Now, commit first file and create another, but do not add.
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" add -A > /dev/null
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" commit -m "First commit" > /dev/null
  assert_git_clean "${BATS_TEST_TMPDIR}/fixture/git_repo"
  mktouch "${BATS_TEST_TMPDIR}/fixture/git_repo/other_uncommitted_file"
  run assert_git_clean "${BATS_TEST_TMPDIR}/fixture/git_repo"
  assert_failure
}

@test "assert_git_not_clean" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/git_repo"
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" init > /dev/null
  assert_git_repo "${BATS_TEST_TMPDIR}/fixture/git_repo"

  run assert_git_not_clean "${BATS_TEST_TMPDIR}/fixture/git_repo"
  assert_failure

  mktouch "${BATS_TEST_TMPDIR}/fixture/git_repo/uncommitted_file"
  assert_git_not_clean "${BATS_TEST_TMPDIR}/fixture/git_repo"


  # Now, commit first file and create another, but do not add.
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" add -A > /dev/null
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" commit -m "First commit" > /dev/null
  run assert_git_not_clean "${BATS_TEST_TMPDIR}/fixture/git_repo"
  assert_failure
  mktouch "${BATS_TEST_TMPDIR}/fixture/git_repo/other_uncommitted_file"
  assert_git_not_clean "${BATS_TEST_TMPDIR}/fixture/git_repo"
}

@test "assert_git_file_is_tracked" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/git_repo"
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/not_git_repo"
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" init > /dev/null
  assert_git_repo "${BATS_TEST_TMPDIR}/fixture/git_repo"
  touch "${BATS_TEST_TMPDIR}/fixture/git_repo/1.txt"
  touch "${BATS_TEST_TMPDIR}/fixture/git_repo/2.txt"
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" add 1.txt > /dev/null
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" commit -m "some message" > /dev/null

  assert_git_file_is_tracked "1.txt" "${BATS_TEST_TMPDIR}/fixture/git_repo"

  run assert_git_file_is_tracked "2.txt" "${BATS_TEST_TMPDIR}/fixture/git_repo"
  assert_failure

  run assert_git_file_is_tracked "1.txt" "${BATS_TEST_TMPDIR}/fixture/not_git_repo"
  assert_failure
}

@test "assert_git_file_is_not_tracked" {
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/git_repo"
  prepare_fixture_dir "${BATS_TEST_TMPDIR}/fixture/not_git_repo"
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" init > /dev/null
  assert_git_repo "${BATS_TEST_TMPDIR}/fixture/git_repo"
  touch "${BATS_TEST_TMPDIR}/fixture/git_repo/1.txt"
  touch "${BATS_TEST_TMPDIR}/fixture/git_repo/2.txt"
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" add 1.txt > /dev/null
  git --work-tree="${BATS_TEST_TMPDIR}/fixture/git_repo" --git-dir="${BATS_TEST_TMPDIR}/fixture/git_repo/.git" commit -m "some message" > /dev/null

  assert_git_file_is_not_tracked "2.txt" "${BATS_TEST_TMPDIR}/fixture/git_repo"

  run assert_git_file_is_not_tracked "1.txt" "${BATS_TEST_TMPDIR}/fixture/git_repo"
  assert_failure

  run assert_git_file_is_not_tracked "2.txt" "${BATS_TEST_TMPDIR}/fixture/not_git_repo"
  assert_failure
}

@test "assert_binary_files_equal" {
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/fixture1.png"
  echo "some other file" > "${BATS_TEST_TMPDIR}/fixture2.png"

  assert_binary_files_equal "${BATS_TEST_TMPDIR}/fixture1.png" "${BATS_TEST_TMPDIR}/fixture1.png"

  run assert_binary_files_equal "${BATS_TEST_TMPDIR}/fixture1.png" "${BATS_TEST_TMPDIR}/fixture2.png"
  assert_failure

  run assert_binary_files_equal "${BATS_TEST_TMPDIR}/fixture3.png" "${BATS_TEST_TMPDIR}/fixture4.png"
  assert_failure

  run assert_binary_files_equal "${BATS_TEST_TMPDIR}/fixture1.png" "${BATS_TEST_TMPDIR}/fixture3.png"
  assert_failure
}

@test "assert_binary_files_not_equal" {
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/fixture1.png"
  echo "some other file" > "${BATS_TEST_TMPDIR}/fixture2.png"

  assert_binary_files_not_equal "${BATS_TEST_TMPDIR}/fixture1.png" "${BATS_TEST_TMPDIR}/fixture2.png"

  run assert_binary_files_not_equal "${BATS_TEST_TMPDIR}/fixture1.png" "${BATS_TEST_TMPDIR}/fixture1.png"
  assert_failure

  run assert_binary_files_not_equal "${BATS_TEST_TMPDIR}/fixture3.png" "${BATS_TEST_TMPDIR}/fixture1.png"
  assert_failure

  run assert_binary_files_not_equal "${BATS_TEST_TMPDIR}/fixture1.png" "${BATS_TEST_TMPDIR}/fixture3.png"
  assert_failure
}

@test "assert_dirs_equal" {
  # Assert that files in the root are equal.
  mkdir -p "${BATS_TEST_TMPDIR}/t11"
  mkdir -p "${BATS_TEST_TMPDIR}/t12"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t11/fixture1.png"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t12/fixture1.png"
  assert_dirs_equal "${BATS_TEST_TMPDIR}/t11" "${BATS_TEST_TMPDIR}/t12"

  # Assert that files in the root are not equal.
  echo "some other file" > "${BATS_TEST_TMPDIR}/t12/fixture1.png"
  run assert_dirs_equal "${BATS_TEST_TMPDIR}/t11" "${BATS_TEST_TMPDIR}/t12"
  assert_failure

  # Assert that files in the subdirs are equal.
  mkdir -p "${BATS_TEST_TMPDIR}/t31/subdir"
  mkdir -p "${BATS_TEST_TMPDIR}/t32/subdir"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t31/subdir/fixture1.png"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t32/subdir/fixture1.png"
  assert_dirs_equal "${BATS_TEST_TMPDIR}/t31" "${BATS_TEST_TMPDIR}/t32"

  # Assert that files in the subdirs are not equal.
  echo "some other file" > "${BATS_TEST_TMPDIR}/t32/subdir/fixture1.png"
  run assert_dirs_equal "${BATS_TEST_TMPDIR}/t31" "${BATS_TEST_TMPDIR}/t32"
  assert_failure

  # Assert that files in the root and subdirs are equal.
  mkdir -p "${BATS_TEST_TMPDIR}/t41/subdir"
  mkdir -p "${BATS_TEST_TMPDIR}/t42/subdir"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t41/fixture1.png"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t41/.hidden"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t41/subdir/fixture1.png"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t42/fixture1.png"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t42/.hidden"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t42/subdir/fixture1.png"
  assert_dirs_equal "${BATS_TEST_TMPDIR}/t41" "${BATS_TEST_TMPDIR}/t42"

  # Assert that files in the root and subdirs are not equal.
  echo "some other file" > "${BATS_TEST_TMPDIR}/t42/subdir/fixture1.png"
  run assert_dirs_equal "${BATS_TEST_TMPDIR}/t41" "${BATS_TEST_TMPDIR}/t42"
  assert_failure

  # Assert that missing files trigger a failure.
  mkdir -p "${BATS_TEST_TMPDIR}/t51/subdir"
  mkdir -p "${BATS_TEST_TMPDIR}/t52/subdir"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t51/fixture1.png"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t51/.hidden"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t51/subdir/fixture1.png"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t51/subdir/fixture2.png"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t52/fixture1.png"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t52/.hidden"
  cp "${BATS_TEST_DIRNAME}/fixtures/fixture.png" "${BATS_TEST_TMPDIR}/t52/subdir/fixture1.png"
  run assert_dirs_equal "${BATS_TEST_TMPDIR}/t51" "${BATS_TEST_TMPDIR}/t52"
  assert_failure

  # Assert non-existing dirs are failing.
  run assert_dirs_equal "${BATS_TEST_TMPDIR}/t61" "${BATS_TEST_TMPDIR}/t62"
  assert_failure
}

@test "mktouch" {
  assert_file_not_exists "${BATS_TEST_TMPDIR}/dir1/dir2/dir3/file.txt"
  mktouch "${BATS_TEST_TMPDIR}/dir1/dir2/dir3/file.txt"
  assert_file_exists "${BATS_TEST_TMPDIR}/dir1/dir2/dir3/file.txt"
}

@test "read_env" {
  pushd "${BATS_TEST_TMPDIR}"

  assert_file_not_exists ".env"

  echo "VAR1=val1" >> .env
  echo "VAR2=val2" >> .env
  run read_env "\$VAR1"
  assert_output_contains "val1"
  run read_env "\$VAR2"
  assert_output_contains "val2"

  popd
}

@test "trim_file" {
  echo "line1" >> "${BATS_TEST_TMPDIR}/file.txt"
  echo "line2" >> "${BATS_TEST_TMPDIR}/file.txt"
  echo "line3" >> "${BATS_TEST_TMPDIR}/file.txt"

  trim_file "${BATS_TEST_TMPDIR}/file.txt"

  assert_file_contains "${BATS_TEST_TMPDIR}/file.txt" "line1"
  assert_file_contains "${BATS_TEST_TMPDIR}/file.txt" "line2"
  assert_file_not_contains "${BATS_TEST_TMPDIR}/file.txt" "line3"

  trim_file "${BATS_TEST_TMPDIR}/file.txt"

  assert_file_contains "${BATS_TEST_TMPDIR}/file.txt" "line1"
  assert_file_not_contains "${BATS_TEST_TMPDIR}/file.txt" "line2"
  assert_file_not_contains "${BATS_TEST_TMPDIR}/file.txt" "line3"
}

@test "add_var_to_file and restore_file" {
  rm -fr /tmp/bkp

  echo "line1" >> "${BATS_TEST_TMPDIR}/.env"
  echo "line2" >> "${BATS_TEST_TMPDIR}/.env"

  add_var_to_file "${BATS_TEST_TMPDIR}/.env" "VAR" "value"

  assert_file_exists "${BATS_TEST_TMPDIR}/.env"
  assert_file_contains "${BATS_TEST_TMPDIR}/.env" "line1"
  assert_file_contains "${BATS_TEST_TMPDIR}/.env" "line2"
  assert_file_contains "${BATS_TEST_TMPDIR}/.env" "VAR=value"

  assert_file_exists "/tmp/bkp/${BATS_TEST_TMPDIR}/.env"
  assert_file_contains "/tmp/bkp/${BATS_TEST_TMPDIR}/.env" "line1"
  assert_file_contains "/tmp/bkp/${BATS_TEST_TMPDIR}/.env" "line2"
  assert_file_not_contains "/tmp/bkp/${BATS_TEST_TMPDIR}/.env" "VAR=value"

  restore_file "${BATS_TEST_TMPDIR}/.env"

  assert_file_exists "${BATS_TEST_TMPDIR}/.env"
  assert_file_contains "${BATS_TEST_TMPDIR}/.env" "line1"
  assert_file_contains "${BATS_TEST_TMPDIR}/.env" "line2"
  assert_file_not_contains "${BATS_TEST_TMPDIR}/.env" "VAR=value"
}
