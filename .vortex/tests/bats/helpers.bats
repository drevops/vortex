#!/usr/bin/env bats
#
# Tests for Vortex Bats helpers.
#
# shellcheck disable=SC2129

load _helper.bash

@test "helper_vortex" {
  echo "     > Bats version: ${BATS_VERSION}" >&3

  [ "${BATS_TMPDIR}" != "" ]
  echo "     > BATS temp dir:      ${BATS_TMPDIR}" >&3

  [ "${BATS_RUN_TMPDIR}" != "" ]
  echo "     > BATS run temp dir:  ${BATS_RUN_TMPDIR}" >&3

  [ "${BATS_FILE_TMPDIR}" != "" ]
  echo "     > BATS file temp dir: ${BATS_FILE_TMPDIR}" >&3

  [ "${BATS_TEST_TMPDIR}" != "" ]
  echo "     > BATS test temp dir: ${BATS_TEST_TMPDIR}" >&3

  [ "${BATS_SUITE_TMPDIR}" != "" ]
  echo "     > BATS suit temp dir: ${BATS_SUITE_TMPDIR}" >&3

  [ "${ROOT_DIR}" != "" ]
  echo "     > Current dir:        ${ROOT_DIR}" >&3
  assert_not_contains "//" "${ROOT_DIR}"

  [ "${BUILD_DIR}" != "" ]
  echo "     > Build dir:          ${BUILD_DIR}" >&3
  assert_not_contains "//" "${BUILD_DIR}"

  [ "${CURRENT_PROJECT_DIR}" != "" ]
  echo "     > Project dir:        ${CURRENT_PROJECT_DIR}" >&3
  assert_not_contains "//" "${CURRENT_PROJECT_DIR}"

  [ "${DST_PROJECT_DIR}" != "" ]
  echo "     > DST dir:            ${DST_PROJECT_DIR}" >&3
  assert_not_contains "//" "${DST_PROJECT_DIR}"

  [ "${LOCAL_REPO_DIR}" != "" ]
  echo "     > Local repo dir:     ${LOCAL_REPO_DIR}" >&3
  assert_not_contains "//" "${LOCAL_REPO_DIR}"

  [ "${APP_TMP_DIR}" != "" ]
  echo "     > App temp dir:       ${APP_TMP_DIR}" >&3
  assert_not_contains "//" "${APP_TMP_DIR}"
}
