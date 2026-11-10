#!/usr/bin/env bats
##
# Unit tests for the 'vortex-fetch-files' script.
#
# shellcheck disable=SC2030,SC2031,SC2016

load ../_helper.bash

# Replaces each fetch script with a stub that names the source it serves.
stub_fetch_scripts() {
  stub_sibling "vortex-fetch-files-url" "Fetched from url."
  stub_sibling "vortex-fetch-files-acquia" "Fetched from acquia."
  stub_sibling "vortex-fetch-files-lagoon" "Fetched from lagoon."
}

@test "fetch-files: Dispatch to the script of each source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_fetch_scripts
  export VORTEX_FETCH_FILES_DIR="files"

  for source in url acquia lagoon; do
    export VORTEX_FETCH_FILES_SOURCE="${source}"

    run .vortex/tooling/src/vortex-fetch-files
    assert_success
    assert_output_contains "[INFO] Started files fetch."
    assert_output_contains "Fetched from ${source}."
    assert_output_contains "[ OK ] Finished files fetch."
  done

  popd >/dev/null
}

@test "fetch-files: Use url source by default" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_fetch_scripts
  unset VORTEX_FETCH_FILES_SOURCE
  export VORTEX_FETCH_FILES_DIR="files"

  run .vortex/tooling/src/vortex-fetch-files
  assert_success
  assert_output_contains "Fetched from url."
  assert_output_contains "[ OK ] Finished files fetch."

  popd >/dev/null
}

@test "fetch-files: Create missing files directory" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_fetch_scripts
  rm -rf files
  export VORTEX_FETCH_FILES_SOURCE="url"
  export VORTEX_FETCH_FILES_DIR="files"

  run .vortex/tooling/src/vortex-fetch-files
  assert_success
  assert_output_contains "[TASK] Creating directory files."
  assert_output_contains "[ OK ] Created directory files."
  assert_dir_exists "files"

  run .vortex/tooling/src/vortex-fetch-files
  assert_success
  assert_output_not_contains "Creating directory files."

  popd >/dev/null
}

@test "fetch-files: Use the default files directory" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # The stub prints the directory the router passes on.
  mkdir -p .vortex/tooling/src
  printf '#!/usr/bin/env bash\necho "Directory ${VORTEX_FETCH_FILES_DIR}."\n' >.vortex/tooling/src/vortex-fetch-files-url
  chmod +x .vortex/tooling/src/vortex-fetch-files-url

  export VORTEX_FETCH_FILES_SOURCE="url"
  unset VORTEX_FETCH_FILES_DIR
  export WEBROOT="docroot"

  run .vortex/tooling/src/vortex-fetch-files
  assert_success
  assert_output_contains "Directory ./docroot/sites/default/files."
  assert_dir_exists "docroot/sites/default/files"

  popd >/dev/null
}

@test "fetch-files: Skip when disabled" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_fetch_scripts
  export VORTEX_FETCH_FILES_PROCEED="0"
  export VORTEX_FETCH_FILES_SOURCE="url"

  run .vortex/tooling/src/vortex-fetch-files
  assert_success
  assert_output_contains "[INFO] Started files fetch."
  assert_output_contains "[ OK ] Skipped files fetch as VORTEX_FETCH_FILES_PROCEED is not set to 1."
  assert_output_not_contains "Fetched from url."
  assert_output_not_contains "Finished files fetch."

  popd >/dev/null
}

@test "fetch-files: Skip fetching with none source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_fetch_scripts
  rm -rf files
  export VORTEX_FETCH_FILES_SOURCE="none"
  export VORTEX_FETCH_FILES_DIR="files"
  export VORTEX_FETCH_FILES_SEMAPHORE=".files-fetched"

  run .vortex/tooling/src/vortex-fetch-files
  assert_success
  assert_output_contains '[ OK ] Skipped fetching as VORTEX_FETCH_FILES_SOURCE is set to "none".'
  assert_output_not_contains "Fetched from"
  assert_output_not_contains "Finished files fetch."
  assert_dir_not_exists "files"
  assert_file_not_exists ".files-fetched"

  popd >/dev/null
}

@test "fetch-files: Fail with unsupported source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_fetch_scripts
  export VORTEX_FETCH_FILES_SOURCE="ftp"
  export VORTEX_FETCH_FILES_DIR="files"

  run .vortex/tooling/src/vortex-fetch-files
  assert_failure
  assert_output_contains '[FAIL] Unsupported files source "ftp" in VORTEX_FETCH_FILES_SOURCE. Use one of: url, acquia, lagoon, none.'
  assert_output_not_contains "Fetched from"

  popd >/dev/null
}

@test "fetch-files: Fail when the source script fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .vortex/tooling/src
  printf '#!/usr/bin/env bash\necho "Source failed."\nexit 3\n' >.vortex/tooling/src/vortex-fetch-files-url
  chmod +x .vortex/tooling/src/vortex-fetch-files-url

  export VORTEX_FETCH_FILES_SOURCE="url"
  export VORTEX_FETCH_FILES_DIR="files"
  export VORTEX_FETCH_FILES_SEMAPHORE=".files-fetched"

  run .vortex/tooling/src/vortex-fetch-files
  assert_failure
  assert_output_contains "Source failed."
  assert_output_not_contains "Finished files fetch."
  assert_file_not_exists ".files-fetched"

  popd >/dev/null
}

@test "fetch-files: Create semaphore file after fetch" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_fetch_scripts
  rm -f .files-fetched
  export VORTEX_FETCH_FILES_SOURCE="url"
  export VORTEX_FETCH_FILES_DIR="files"
  export VORTEX_FETCH_FILES_SEMAPHORE=".files-fetched"

  run .vortex/tooling/src/vortex-fetch-files
  assert_success
  assert_file_exists ".files-fetched"

  popd >/dev/null
}
