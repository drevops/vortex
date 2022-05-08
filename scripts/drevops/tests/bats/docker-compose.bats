#!/usr/bin/env bats
#
# Test for docker-compose format and default variables.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper
load _helper_drevops

@test "Docker Compose: default with .env" {
  prepare_docker_compose

  cp "${CUR_DIR}/.env" .env

  substep "Validate configuration"
  run docker-compose -f docker-compose.yml config
  assert_success

  substep "Compare with fixture"
  docker-compose -f docker-compose.yml config > docker-compose.actual.yml

  prepare_docker_compose_fixture

  assert_files_equal docker-compose.actual.yml docker-compose.expected.yml
}

@test "Docker Compose: default without .env" {
  prepare_docker_compose

  substep "Validate configuration"
  run docker-compose -f docker-compose.yml config
  assert_success

  substep "Compare with fixture"
  docker-compose -f docker-compose.yml config > docker-compose.actual.yml

  prepare_docker_compose_fixture

  assert_files_equal docker-compose.actual.yml docker-compose.expected.yml
}

prepare_docker_compose() {
  cp "${CUR_DIR}/docker-compose.yml" docker-compose.yml

  # In order for tests to pass locally and in CI, we need to replicate the
  # environment locally to be the same as in CI.
  export CI=true

  # Process codebase to run in CI
  sed -i -e "/###/d" docker-compose.yml && sed -i -e "s/##//" docker-compose.yml
}

prepare_docker_compose_fixture() {
  cp "${CUR_DIR}/scripts/drevops/tests/bats/fixtures/docker-compose.fixture.yml" docker-compose.expected.yml
  replace_string_content "FIXTURE_CUR_DIR" "${CURRENT_PROJECT_DIR}" "${CURRENT_PROJECT_DIR}"

  # Replace symlink /private paths in MacOS.
  replace_string_content "/private/var/" "/var/" "${CURRENT_PROJECT_DIR}"
}
