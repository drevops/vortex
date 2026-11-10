#!/usr/bin/env bats
##
# Unit tests for the 'vortex-fetch-files-url' script.
#
# shellcheck disable=SC2030,SC2031,SC2016

load ../_helper.bash

# Creates a files archive in the build directory and prints its path.
fixture_files_archive() {
  local name="${1}"
  local src="${BUILD_DIR}/archive_src"

  mkdir -p "${src}/inline-images"
  echo "image" >"${src}/inline-images/photo.jpg"
  echo "document" >"${src}/document.pdf"

  case "${name}" in
    *.tar) tar -cf "${BUILD_DIR}/${name}" -C "${src}" . ;;
    *) tar -czf "${BUILD_DIR}/${name}" -C "${src}" . ;;
  esac

  echo "${BUILD_DIR}/${name}"
}

@test "fetch-files-url: Fetch and extract archive" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  for name in files.tar.gz files.tgz files.tar; do
    rm -rf files
    archive="$(fixture_files_archive "${name}")"

    export VORTEX_FETCH_FILES_URL="file://${archive}"
    export VORTEX_FETCH_FILES_DIR="files"

    run .vortex/tooling/src/vortex-fetch-files-url
    assert_success
    assert_output_contains "[INFO] Started files fetch from URL."
    assert_output_contains "[TASK] Fetching files archive."
    assert_output_contains "[ OK ] Fetched files archive."
    assert_output_contains "[TASK] Extracting files archive into files."
    assert_output_contains "[ OK ] Extracted files archive into files."
    assert_output_contains "[ OK ] Finished files fetch from URL."

    assert_file_contains "files/document.pdf" "document"
    assert_file_contains "files/inline-images/photo.jpg" "image"
  done

  popd >/dev/null
}

@test "fetch-files-url: Keep local files missing from the archive" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf files
  mkdir -p files
  echo "local" >files/local.txt
  archive="$(fixture_files_archive "files.tar.gz")"

  export VORTEX_FETCH_FILES_URL="file://${archive}"
  export VORTEX_FETCH_FILES_DIR="files"

  run .vortex/tooling/src/vortex-fetch-files-url
  assert_success
  assert_file_contains "files/local.txt" "local"
  assert_file_contains "files/document.pdf" "document"

  popd >/dev/null
}

@test "fetch-files-url: Use the default files directory" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  archive="$(fixture_files_archive "files.tar.gz")"

  export VORTEX_FETCH_FILES_URL="file://${archive}"
  unset VORTEX_FETCH_FILES_DIR
  export WEBROOT="docroot"

  run .vortex/tooling/src/vortex-fetch-files-url
  assert_success
  assert_file_contains "docroot/sites/default/files/document.pdf" "document"

  popd >/dev/null
}

@test "fetch-files-url: Fail when VORTEX_FETCH_FILES_URL is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_FILES_URL=""
  export VORTEX_FETCH_FILES_DIR="files"

  run .vortex/tooling/src/vortex-fetch-files-url
  assert_failure
  assert_output_contains "[INFO] Started files fetch from URL."
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_FILES_URL."

  popd >/dev/null
}

@test "fetch-files-url: Fail when the archive cannot be fetched" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_status "${mock_curl}" 22 1

  export VORTEX_FETCH_FILES_URL="https://user:secret@example.com/files.tar.gz"
  export VORTEX_FETCH_FILES_DIR="files"

  run .vortex/tooling/src/vortex-fetch-files-url
  assert_failure
  assert_output_contains "[FAIL] Unable to fetch files archive from the URL in VORTEX_FETCH_FILES_URL."
  assert_output_not_contains "secret"
  assert_output_not_contains "Finished files fetch from URL."
  # The -f flag makes curl exit non-zero on HTTP 4xx/5xx instead of saving the
  # error body as the archive.
  assert_string_contains "$(mock_get_call_args "${mock_curl}" 1)" "-fLs"

  popd >/dev/null
}

@test "fetch-files-url: Fail when the archive cannot be extracted" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  echo "not an archive" >"${BUILD_DIR}/files.tar.gz"

  export VORTEX_FETCH_FILES_URL="file://${BUILD_DIR}/files.tar.gz"
  export VORTEX_FETCH_FILES_DIR="files"

  run .vortex/tooling/src/vortex-fetch-files-url
  assert_failure
  assert_output_contains "[ OK ] Fetched files archive."
  assert_output_contains "[FAIL] Unable to extract files archive into files."
  assert_output_not_contains "Finished files fetch from URL."

  popd >/dev/null
}
