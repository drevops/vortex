#!/usr/bin/env bats
#
# Unit tests for the 'vortex-fetch-files-lagoon' script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "fetch-files-lagoon: Fetch files successfully" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf files

  declare -a STEPS=(
    "[INFO] Started files fetch from Lagoon."
    "[INFO] Started SSH setup."
    "[TASK] Fetching files from main environment."
    '@rsync -rltz --exclude /css --exclude /js --exclude /php --exclude /styles -e ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o LogLevel=error -o IdentitiesOnly=yes -p 32222 testproject-main@ssh.lagoon.amazeeio.cloud:/app/web/sites/default/files/ files/ # 0 #  # echo "image" > files/photo.jpg'
    "[ OK ] Fetched files from main environment."
    "[ OK ] Finished files fetch from Lagoon."
  )

  export VORTEX_FETCH_FILES_LAGOON_PROJECT="testproject"
  export VORTEX_FETCH_FILES_ENVIRONMENT="main"
  export VORTEX_FETCH_FILES_DIR="files"

  fixture_ssh_key_prepare
  export VORTEX_FETCH_FILES_SSH_FILE=false

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-lagoon
  steps_run "assert" "${mocks[@]}"

  assert_success
  assert_file_contains "files/photo.jpg" "image"

  popd >/dev/null
}

@test "fetch-files-lagoon: Fall back to the database environment and project" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[INFO] Started files fetch from Lagoon."
    "[TASK] Fetching files from develop environment."
    '@rsync -rltz --exclude /css --exclude /js --exclude /php --exclude /styles -e ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o LogLevel=error -o IdentitiesOnly=yes -p 32222 lagoonproject-develop@ssh.lagoon.amazeeio.cloud:/app/docroot/sites/default/files/ files/ # 0'
    "[ OK ] Finished files fetch from Lagoon."
  )

  unset VORTEX_FETCH_FILES_LAGOON_PROJECT VORTEX_FETCH_FILES_ENVIRONMENT
  export LAGOON_PROJECT="lagoonproject"
  export VORTEX_FETCH_DB_ENVIRONMENT="develop"
  export WEBROOT="docroot"
  export VORTEX_FETCH_FILES_DIR="files"

  fixture_ssh_key_prepare
  export VORTEX_FETCH_FILES_SSH_FILE=false

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-lagoon
  steps_run "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null
}

@test "fetch-files-lagoon: Use custom connection, directories and excludes" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[TASK] Fetching files from main environment."
    '@rsync -rltz --exclude /styles -e ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o LogLevel=error -o IdentitiesOnly=yes -p 2020 -i '"${BUILD_DIR}"'/.ssh/id_rsa custom-user@ssh.example.com:/var/files/ local-files/ # 0'
    "[ OK ] Finished files fetch from Lagoon."
  )

  export VORTEX_FETCH_FILES_LAGOON_PROJECT="testproject"
  export VORTEX_FETCH_FILES_LAGOON_SSH_HOST="ssh.example.com"
  export VORTEX_FETCH_FILES_LAGOON_SSH_PORT="2020"
  export VORTEX_FETCH_FILES_LAGOON_SSH_USER="custom-user"
  export VORTEX_FETCH_FILES_LAGOON_REMOTE_DIR="/var/files"
  export VORTEX_FETCH_FILES_DIR="local-files"
  export VORTEX_FETCH_FILES_EXCLUDE="styles"

  fixture_ssh_key_prepare
  fixture_ssh_key
  export VORTEX_FETCH_FILES_SSH_FILE="${BUILD_DIR}/.ssh/id_rsa"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-lagoon
  steps_run "assert" "${mocks[@]}"

  assert_success
  assert_dir_exists "local-files"

  popd >/dev/null
}

@test "fetch-files-lagoon: Fetch every directory with empty excludes" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@rsync -rltz -e ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o LogLevel=error -o IdentitiesOnly=yes -p 32222 testproject-main@ssh.lagoon.amazeeio.cloud:/app/web/sites/default/files/ files/ # 0'
    "[ OK ] Finished files fetch from Lagoon."
  )

  export VORTEX_FETCH_FILES_LAGOON_PROJECT="testproject"
  export VORTEX_FETCH_FILES_ENVIRONMENT="main"
  export VORTEX_FETCH_FILES_DIR="files"
  export VORTEX_FETCH_FILES_EXCLUDE=""

  fixture_ssh_key_prepare
  export VORTEX_FETCH_FILES_SSH_FILE=false

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-lagoon
  steps_run "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null
}

@test "fetch-files-lagoon: Fail when rsync fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[TASK] Fetching files from main environment."
    "@rsync * # 23"
    "- Fetched files from main environment."
    "- Finished files fetch from Lagoon."
  )

  export VORTEX_FETCH_FILES_LAGOON_PROJECT="testproject"
  export VORTEX_FETCH_FILES_ENVIRONMENT="main"
  export VORTEX_FETCH_FILES_DIR="files"

  fixture_ssh_key_prepare
  export VORTEX_FETCH_FILES_SSH_FILE=false

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-lagoon
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null
}
