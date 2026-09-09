#!/usr/bin/env bats
#
# Unit tests for the Vortex BATS helpers.
#
# shellcheck disable=SC2129

load ../_helper.bash

@test "helper_vortex" {
  echo "     > Bats version: ${BATS_VERSION}" >&3

  assert_not_empty "${BATS_TMPDIR}"
  echo "     > BATS temp dir:      ${BATS_TMPDIR}" >&3

  assert_not_empty "${BATS_RUN_TMPDIR}"
  echo "     > BATS run temp dir:  ${BATS_RUN_TMPDIR}" >&3

  assert_not_empty "${BATS_FILE_TMPDIR}"
  echo "     > BATS file temp dir: ${BATS_FILE_TMPDIR}" >&3

  assert_not_empty "${BATS_TEST_TMPDIR}"
  echo "     > BATS test temp dir: ${BATS_TEST_TMPDIR}" >&3

  assert_not_empty "${BATS_SUITE_TMPDIR}"
  echo "     > BATS suit temp dir: ${BATS_SUITE_TMPDIR}" >&3

  assert_not_empty "${ROOT_DIR}"
  echo "     > Current dir:        ${ROOT_DIR}" >&3
  assert_string_not_contains "${ROOT_DIR}" "//"

  assert_not_empty "${BUILD_DIR}"
  echo "     > Build dir:          ${BUILD_DIR}" >&3
  assert_string_not_contains "${BUILD_DIR}" "//"

  assert_not_empty "${CURRENT_PROJECT_DIR}"
  echo "     > Project dir:        ${CURRENT_PROJECT_DIR}" >&3
  assert_string_not_contains "${CURRENT_PROJECT_DIR}" "//"

  assert_not_empty "${DST_PROJECT_DIR}"
  echo "     > DST dir:            ${DST_PROJECT_DIR}" >&3
  assert_string_not_contains "${DST_PROJECT_DIR}" "//"

  assert_not_empty "${LOCAL_REPO_DIR}"
  echo "     > Local repo dir:     ${LOCAL_REPO_DIR}" >&3
  assert_string_not_contains "${LOCAL_REPO_DIR}" "//"

  assert_not_empty "${APP_TMP_DIR}"
  echo "     > App temp dir:       ${APP_TMP_DIR}" >&3
  assert_string_not_contains "${APP_TMP_DIR}" "//"
}
