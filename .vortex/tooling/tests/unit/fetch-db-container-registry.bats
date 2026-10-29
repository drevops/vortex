#!/usr/bin/env bats
##
# Unit tests for the 'vortex-fetch-db-container-registry' script.
#
# shellcheck disable=SC2030,SC2031

load ../_helper.bash

@test "fetch-db-container-registry: Fetch image successfully when not found on host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  # The image is absent from the host, so the inspect is followed by a registry
  # login, a pull, a tag and a layer count. The login script is invoked by its
  # own path rather than through PATH, so it runs for real and its own
  # 'docker login' is call 2.
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 3
  mock_set_side_effect "${mock_docker}" "echo 'tagged image'" 4
  mock_set_side_effect "${mock_docker}" "echo 24" 5

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Not found myorg/myapp image on host."
  assert_output_contains "Fetching myorg/myapp image from the registry."
  assert_output_contains "[ OK ] Fetched myorg/myapp image from the registry."
  assert_output_contains "[ OK ] Database container image myorg/myapp has 24 layers, below the limit of 100."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  assert_string_contains "$(mock_get_call_args "${mock_docker}" 3)" "pull registry.example.com/myorg/myapp"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 4)" "tag registry.example.com/myorg/myapp myorg/myapp"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 5)" "image inspect --format {{len .RootFS.Layers}} myorg/myapp"
  assert_equal "5" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "fetch-db-container-registry: Expand archived image when db.tar exists" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Create mock archive file
  mkdir -p .data
  touch .data/db.tar

  mock_docker=$(mock_command "docker")
  # First call to image inspect fails (not on host), load succeeds, second inspect succeeds (after load)
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'Loaded image: myorg/myapp'" 2
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 3
  mock_set_side_effect "${mock_docker}" "echo 99" 4
  mock_set_side_effect "${mock_docker}" "echo 99" 5

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Not found myorg/myapp image on host."
  assert_output_contains "Found archived database container image file .data/db.tar. Expanding..."
  assert_output_contains "Found expanded myorg/myapp image on host."
  assert_output_not_contains "Discarding the expanded image to fetch it from the registry."
  assert_output_contains "[ OK ] Expanded archived database container image file .data/db.tar."
  assert_output_not_contains "Fetching myorg/myapp image from the registry."
  assert_output_contains "[ OK ] Database container image myorg/myapp has 99 layers, below the limit of 100."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  # The inspect, the load, the second inspect and 2 layer counts: no pull.
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 4)" "image inspect --format {{len .RootFS.Layers}} myorg/myapp"
  assert_equal "5" "$(mock_get_call_num "${mock_docker}")"

  # Clean up
  rm -f .data/db.tar

  popd >/dev/null
}

@test "fetch-db-container-registry: Tag base image as the database container image when a base image is provided" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  # The login, the pull of the base image and the tag. The host image is not
  # inspected.
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 1
  mock_set_side_effect "${mock_docker}" "echo 'pulled base image'" 2
  mock_set_side_effect "${mock_docker}" "echo 'tagged base image'" 3

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Tagging base image drevops/mariadb-drupal-data:26.10.0 as database container image myorg/myapp."
  assert_output_contains "[ OK ] Tagged base image drevops/mariadb-drupal-data:26.10.0 as database container image myorg/myapp."
  assert_output_contains "[ OK ] Finished database data container image fetch."
  assert_output_not_contains "Fetching myorg/myapp image from the registry."

  assert_equal "3" "$(mock_get_call_num "${mock_docker}")"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 2)" "pull drevops/mariadb-drupal-data:26.10.0"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 3)" "tag drevops/mariadb-drupal-data:26.10.0 myorg/myapp"

  popd >/dev/null
}

@test "fetch-db-container-registry: Tag base image instead of expanding the archive" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  touch .data/db.tar

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 1
  mock_set_side_effect "${mock_docker}" "echo 'pulled base image'" 2
  mock_set_side_effect "${mock_docker}" "echo 'tagged base image'" 3

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[ OK ] Tagged base image drevops/mariadb-drupal-data:26.10.0 as database container image myorg/myapp."
  assert_output_not_contains "Found archived database container image file .data/db.tar. Expanding..."

  # The login, the pull and the tag: the archive is not loaded.
  assert_equal "3" "$(mock_get_call_num "${mock_docker}")"

  rm -f .data/db.tar

  popd >/dev/null
}

@test "fetch-db-container-registry: Fail when the base image pull fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 1
  mock_set_side_effect "${mock_docker}" "exit 1" 2

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_failure
  assert_output_contains "Tagging base image drevops/mariadb-drupal-data:26.10.0 as database container image myorg/myapp."
  assert_output_not_contains "Tagged base image"
  assert_output_not_contains "Finished database data container image fetch."

  # The login and the failed pull: nothing is tagged.
  assert_equal "2" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "fetch-db-container-registry: Resolve indexed base image variable with VORTEX_DB_INDEX" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 1
  mock_set_side_effect "${mock_docker}" "echo 'pulled base image'" 2
  mock_set_side_effect "${mock_docker}" "echo 'tagged base image'" 3

  export VORTEX_DB_INDEX="2"
  export VORTEX_DB2_IMAGE="myorg/migration-db"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[ OK ] Tagged base image drevops/mariadb-drupal-data:26.10.0 as database container image myorg/migration-db."

  assert_string_contains "$(mock_get_call_args "${mock_docker}" 3)" "tag drevops/mariadb-drupal-data:26.10.0 myorg/migration-db"

  popd >/dev/null
}

@test "fetch-db-container-registry: Fetch image when only the shared base image variable is set" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  # The shared variable does not switch a fetch to the base image: the image is
  # inspected on the host, then the registry login and the pull follow.
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 3
  mock_set_side_effect "${mock_docker}" "echo 'tagged image'" 4

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_DB_IMAGE_BASE="drevops/mariadb-drupal-data:26.10.0"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_not_contains "Tagging base image"
  assert_output_contains "Fetching myorg/myapp image from the registry."

  # The inspect, the login, the pull, the tag and the layer count.
  assert_equal "5" "$(mock_get_call_num "${mock_docker}")"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 3)" "pull registry.example.com/myorg/myapp"

  popd >/dev/null
}

@test "fetch-db-container-registry: Skip fetch when image exists on host and no archive exists" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  # The image is found on the host and there is no archive to expand, so the
  # inspect and the layer count are the only calls - no registry login and no
  # pull follow them.
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 1
  mock_set_side_effect "${mock_docker}" "echo 24" 2

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Found myorg/myapp image on host."
  assert_output_contains "Using existing myorg/myapp image on host."
  assert_output_contains "Fetch will not proceed."
  assert_output_contains "Remove existing image or set VORTEX_FETCH_DB_FORCE value to 1 to force fetch."
  assert_output_not_contains "Fetching myorg/myapp image from the registry."
  assert_output_contains "[ OK ] Database container image myorg/myapp has 24 layers, below the limit of 100."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  assert_string_contains "$(mock_get_call_args "${mock_docker}" 2)" "image inspect --format {{len .RootFS.Layers}} myorg/myapp"
  assert_equal "2" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "fetch-db-container-registry: Fetch image when it exists on host and fetch is forced" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 3
  mock_set_side_effect "${mock_docker}" "echo 'tagged image'" 4

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"
  export VORTEX_FETCH_DB_FORCE=1

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Found myorg/myapp image on host."
  assert_output_not_contains "Using existing myorg/myapp image on host."
  assert_output_contains "Fetching myorg/myapp image from the registry."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  # The inspect, the login, the pull, the tag and the layer count.
  assert_equal "5" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "fetch-db-container-registry: Skip fetch when image exists on host and indexed fetch is not forced" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 1

  export VORTEX_DB_INDEX="2"
  export VORTEX_DB2_IMAGE="myorg/migration-db"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "Using existing myorg/migration-db image on host."
  # The message names the indexed variable that the script actually reads.
  assert_output_contains "Remove existing image or set VORTEX_FETCH_DB2_FORCE value to 1 to force fetch."
  assert_output_not_contains "Fetching myorg/migration-db image from the registry."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  # The inspect and the layer count.
  assert_equal "2" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "fetch-db-container-registry: Fetch image when it exists on host and indexed fetch is forced" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 3
  mock_set_side_effect "${mock_docker}" "echo 'tagged image'" 4

  export VORTEX_DB_INDEX="2"
  export VORTEX_DB2_IMAGE="myorg/migration-db"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_DB_DIR=".data"
  export VORTEX_FETCH_DB2_FORCE=1

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_not_contains "Using existing myorg/migration-db image on host."
  assert_output_contains "Fetching myorg/migration-db image from the registry."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  # The inspect, the login, the pull, the tag and the layer count.
  assert_equal "5" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "fetch-db-container-registry: Fetch image when archive expansion fails and image exists on host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  touch .data/db.tar

  mock_docker=$(mock_command "docker")
  # The archive is authoritative when present, so a failed expansion falls
  # through to the registry rather than reusing the image already on the host.
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 1
  mock_set_side_effect "${mock_docker}" "echo 'Loaded image: myorg/myapp'" 2
  mock_set_side_effect "${mock_docker}" "exit 1" 3
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 4
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 5
  mock_set_side_effect "${mock_docker}" "echo 'tagged image'" 6

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "Found myorg/myapp image on host."
  assert_output_contains "Found archived database container image file .data/db.tar. Expanding..."
  assert_output_contains "Not found expanded myorg/myapp image on host."
  assert_output_not_contains "Using existing myorg/myapp image on host."
  assert_output_contains "Fetching myorg/myapp image from the registry."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  # The inspect, the load, the second inspect, the login, the pull, the tag and
  # the layer count. A failed expansion has no layers to count.
  assert_equal "7" "$(mock_get_call_num "${mock_docker}")"

  rm -f .data/db.tar

  popd >/dev/null
}

@test "fetch-db-container-registry: Discard expanded archive at the layer limit and fetch from the registry" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  touch .data/db.tar

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'Loaded image: myorg/myapp'" 2
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 3
  mock_set_side_effect "${mock_docker}" "echo 100" 4
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 5
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 6
  mock_set_side_effect "${mock_docker}" "echo 'tagged image'" 7
  mock_set_side_effect "${mock_docker}" "echo 24" 8

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "Found expanded myorg/myapp image on host."
  assert_output_contains "Expanded myorg/myapp image has 100 layers, at or above the limit of 100."
  assert_output_contains "Discarding the expanded image to fetch it from the registry."
  assert_output_contains "[ OK ] Expanded archived database container image file .data/db.tar."
  assert_output_contains "Fetching myorg/myapp image from the registry."
  assert_output_contains "[ OK ] Database container image myorg/myapp has 24 layers, below the limit of 100."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  # The tag of the pulled image replaces the tag of the discarded archive.
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 4)" "image inspect --format {{len .RootFS.Layers}} myorg/myapp"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 6)" "pull registry.example.com/myorg/myapp"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 7)" "tag registry.example.com/myorg/myapp myorg/myapp"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 8)" "image inspect --format {{len .RootFS.Layers}} myorg/myapp"
  assert_equal "8" "$(mock_get_call_num "${mock_docker}")"

  rm -f .data/db.tar

  popd >/dev/null
}

@test "fetch-db-container-registry: Discard expanded archive at a custom indexed layer limit" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  touch .data/db.tar

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'Loaded image: myorg/migration-db'" 2
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 3
  mock_set_side_effect "${mock_docker}" "echo 31" 4
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 5
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 6
  mock_set_side_effect "${mock_docker}" "echo 'tagged image'" 7
  mock_set_side_effect "${mock_docker}" "echo 24" 8

  export VORTEX_DB_INDEX="2"
  export VORTEX_DB2_IMAGE="myorg/migration-db"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_DB_DIR=".data"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_MAX_LAYERS=30

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "Expanded myorg/migration-db image has 31 layers, at or above the limit of 30."
  assert_output_contains "Fetching myorg/migration-db image from the registry."
  assert_output_contains "[ OK ] Database container image myorg/migration-db has 24 layers, below the limit of 30."

  assert_string_contains "$(mock_get_call_args "${mock_docker}" 7)" "tag registry.example.com/myorg/migration-db myorg/migration-db"
  assert_equal "8" "$(mock_get_call_num "${mock_docker}")"

  rm -f .data/db.tar

  popd >/dev/null
}

@test "fetch-db-container-registry: Fail when the pulled image cannot be tagged" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 3
  mock_set_side_effect "${mock_docker}" "exit 1" 4

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_failure
  assert_output_contains "Fetching myorg/myapp image from the registry."
  assert_output_contains "[FAIL] Unable to tag registry.example.com/myorg/myapp image as myorg/myapp."
  assert_output_not_contains "[ OK ] Fetched myorg/myapp image from the registry."
  assert_output_not_contains "Finished database data container image fetch."

  assert_equal "4" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "fetch-db-container-registry: Fail when the fetched image is at the layer limit" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 3
  mock_set_side_effect "${mock_docker}" "echo 'tagged image'" 4
  mock_set_side_effect "${mock_docker}" "echo 100" 5

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_failure
  assert_output_contains "[ OK ] Fetched myorg/myapp image from the registry."
  assert_output_contains "[FAIL] Database container image myorg/myapp has 100 layers, at or above the limit of 100. Docker cannot build on an image with 125 layers. Rebuild the image from a database dump on a clean base image, or raise VORTEX_FETCH_DB_CONTAINER_REGISTRY_MAX_LAYERS."
  assert_output_not_contains "Finished database data container image fetch."

  assert_equal "5" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "fetch-db-container-registry: Fail when the host image is above the indexed layer limit" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 1
  mock_set_side_effect "${mock_docker}" "echo 120" 2

  export VORTEX_DB_INDEX="2"
  export VORTEX_DB2_IMAGE="myorg/migration-db"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_failure
  assert_output_contains "Using existing myorg/migration-db image on host."
  assert_output_contains "[FAIL] Database container image myorg/migration-db has 120 layers, at or above the limit of 100."
  assert_output_contains "raise VORTEX_FETCH_DB2_CONTAINER_REGISTRY_MAX_LAYERS."
  assert_output_not_contains "Finished database data container image fetch."

  assert_equal "2" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "fetch-db-container-registry: Skip the layer check when the layer count cannot be read" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 1
  mock_set_side_effect "${mock_docker}" "echo '<no value>'" 2

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "Using existing myorg/myapp image on host."
  assert_output_not_contains "layers"
  assert_output_contains "[ OK ] Finished database data container image fetch."

  assert_equal "2" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "fetch-db-container-registry: Fail with an invalid layer limit" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  mock_docker=$(mock_command "docker")

  for max_layers in abc 0 -1 1.5 010; do
    export VORTEX_FETCH_DB_CONTAINER_REGISTRY_MAX_LAYERS="${max_layers}"

    run .vortex/tooling/src/vortex-fetch-db-container-registry
    assert_failure
    assert_output_contains "[FAIL] Invalid value \"${max_layers}\" for VORTEX_FETCH_DB_CONTAINER_REGISTRY_MAX_LAYERS. Use a positive integer."
  done

  # The value is validated before any image is inspected or fetched.
  assert_equal "0" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null
}

@test "fetch-db-container-registry: Use default registry when not specified" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled from docker.io'" 3
  mock_set_side_effect "${mock_docker}" "echo 'tagged image'" 4

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  # Don't set VORTEX_FETCH_DB_CONTAINER_REGISTRY to test default
  unset VORTEX_FETCH_DB_CONTAINER_REGISTRY VORTEX_CONTAINER_REGISTRY
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Fetching myorg/myapp image from the registry."
  # The registry reaches the pull target but never the output, so only the
  # recorded arguments show that it defaulted to docker.io.
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 3)" "pull docker.io/myorg/myapp"
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 4)" "tag docker.io/myorg/myapp myorg/myapp"
  assert_output_contains "[ OK ] Finished database data container image fetch."

  popd >/dev/null
}

@test "fetch-db-container-registry: Resolve indexed variables with VORTEX_DB_INDEX" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 3
  mock_set_side_effect "${mock_docker}" "echo 'tagged image'" 4

  # Set database index as used in CI: VORTEX_DB_INDEX=2.
  export VORTEX_DB_INDEX="2"

  # Set the shorthand image variable with index.
  export VORTEX_DB2_IMAGE="myorg/migration-db"

  # Set remaining required variables with index in the DB part.
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Fetching myorg/migration-db image from the registry."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  popd >/dev/null
}

@test "fetch-db-container-registry: Fail when VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER=""
  # Also unset fallback variable
  unset VORTEX_CONTAINER_REGISTRY_USER
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_failure
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER or VORTEX_CONTAINER_REGISTRY_USER."

  popd >/dev/null
}

@test "fetch-db-container-registry: Fail when VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS=""
  # Also unset fallback variable
  unset VORTEX_CONTAINER_REGISTRY_PASS

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_failure
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS or VORTEX_CONTAINER_REGISTRY_PASS."

  popd >/dev/null
}

@test "fetch-db-container-registry: Fail when VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE=""
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_failure
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE or VORTEX_DB_IMAGE."

  popd >/dev/null
}
