#!/usr/bin/env bats
#
# Test for docs publishing functionality.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2002

load _helper.bash

export BATS_FIXTURE_EXPORT_CODEBASE_ENABLED=1

@test "Docs release" {
  update_local_repo

  export REMOTE_REPO_DIR="${BUILD_DIR}/deployment_remote"
  export DOCS_PUBLISH_REMOTE_URL="${REMOTE_REPO_DIR}"/.git
  prepare_remote_docs_repo

  # The test itself.
  pushd "${LOCAL_REPO_DIR}/.drevops/docs" >/dev/null || exit 1

  substep "Test 1: Publish from branch."
  export DOCS_PUBLISH_SRC_BRANCH="feature/test-branch-first"
  run ./.utils/publish.sh
  assert_success
  # The very first version is set as latest.
  assert_version "feature-test-branch-first" 1

  substep 'Test 2: Followup publish from "feature-test-branch-second" branch.'
  export DOCS_PUBLISH_SRC_BRANCH="feature/test-branch-second"
  run ./.utils/publish.sh
  assert_success
  # Alias for the very first version will be only changed on the next publish
  # of the stable version.
  assert_version "feature-test-branch-first" 1
  assert_version "feature-test-branch-second"

  substep 'Test 3: Followup publish from "1.0.0" tag.'
  export DOCS_PUBLISH_SRC_BRANCH="main"
  export DOCS_PUBLISH_SRC_TAG="1.0.0"
  run ./.utils/publish.sh
  assert_success
  assert_version "feature-test-branch-first"
  assert_version "feature-test-branch-second"
  assert_version "1.0.0" 1

  substep 'Test 4: Followup publish from "1.1.0" tag.'
  export DOCS_PUBLISH_SRC_BRANCH="main"
  export DOCS_PUBLISH_SRC_TAG="1.1.0"
  run ./.utils/publish.sh
  assert_success
  assert_version "feature-test-branch-first"
  assert_version "feature-test-branch-second"
  assert_version "1.0.0"
  assert_version "1.1.0" 1

  substep 'Test 5: Followup publish from "main" branch.'
  export DOCS_PUBLISH_SRC_BRANCH="main"
  export DOCS_PUBLISH_CANARY_BRANCH="main"
  export DOCS_PUBLISH_SRC_TAG=""
  run ./.utils/publish.sh
  assert_success
  assert_version "feature-test-branch-first"
  assert_version "feature-test-branch-second"
  assert_version "1.0.0"
  assert_version "1.1.0" 1
  assert_version "canary"

  popd >/dev/null || exit 1
}

assert_version() {
  local expected_version="$1"
  local expected_has_alias="${2:-0}"

  assert_dir_exists "${REMOTE_REPO_DIR}/${expected_version}"
  assert_dir_exists "${REMOTE_REPO_DIR}/latest"
  assert_file_exists "${REMOTE_REPO_DIR}/CNAME"
  assert_file_exists "${REMOTE_REPO_DIR}/versions.json"

  actual_has_version="$(cat "${REMOTE_REPO_DIR}/versions.json" | jq 'any(.[]; .version == "'"${expected_version}"'")')"
  assert_equal "true" "${actual_has_version}"

  actual_has_alias="$(cat "${REMOTE_REPO_DIR}/versions.json" | jq 'any(.[]; .version == "'"${expected_version}"'" and any(.aliases[]?; . == "latest"))')"
  if [ "${expected_has_alias}" -eq 1 ]; then
    assert_equal "true" "${actual_has_alias}"
  else
    assert_equal "false" "${actual_has_alias}"
  fi
}

update_local_repo() {
  # Need to do this as '.drevops' dir is excluded in .gitattributes.
  substep "Copying docs to ${LOCAL_REPO_DIR}."
  mkdir -p "${LOCAL_REPO_DIR}/.drevops/docs"
  cp -r "${ROOT_DIR}/.drevops/docs/." "${LOCAL_REPO_DIR}/.drevops/docs"
}

prepare_remote_docs_repo() {
  # "Remote" repository to deploy the docs to. It is located in the host
  # filesystem and just treated as a remote for published docs.
  substep "Preparing remote repo directory ${REMOTE_REPO_DIR}."
  fixture_prepare_dir "${REMOTE_REPO_DIR}"
  git_init 1 "${REMOTE_REPO_DIR}"
  echo "Initial commit" >"${REMOTE_REPO_DIR}/README.md"
  git_add_all_commit "Initial commit" "${REMOTE_REPO_DIR}"
}
