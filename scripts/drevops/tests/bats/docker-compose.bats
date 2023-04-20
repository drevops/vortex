#!/usr/bin/env bats
#
# Test for docker compose format and default variables.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash

@test "Docker Compose: default without .env" {
  prepare_docker_compose

  substep "Validate configuration"
  run docker compose -f docker-compose.yml config
  assert_success

  substep "Compare with fixture"
  prepare_docker_compose_fixture

  docker compose -f docker-compose.yml config --format json > docker-compose.actual.json
  echo "" >> docker-compose.actual.json

  assert_files_equal docker-compose.actual.json docker-compose.noenv.json
}

@test "Docker Compose: default with .env" {
  prepare_docker_compose

  cp "${CUR_DIR}/.env" .env

  substep "Validate configuration"
  run docker compose -f docker-compose.yml config
  assert_success

  substep "Compare with fixture"
  prepare_docker_compose_fixture

  docker compose -f docker-compose.yml config --format json > docker-compose.actual.json
  echo "" >> docker-compose.actual.json

  assert_files_equal docker-compose.actual.json docker-compose.env.json
}

@test "Docker Compose: default with .env modified" {
  prepare_docker_compose

  cp "${CUR_DIR}/.env" .env

  echo "COMPOSE_PROJECT_NAME=the_matrix" >> .env
  echo "DREVOPS_APP=/myapp" >> .env
  echo "DREVOPS_WEBROOT=docroot" >> .env
  echo "DREVOPS_DB_DOCKER_IMAGE=myorg/my_db_image" >> .env
  echo "XDEBUG_ENABLE=1" >> .env
  echo "SSMTP_MAILHUB=false" >> .env
  echo "DRUPAL_SHIELD_USER=jane" >> .env
  echo "DRUPAL_SHIELD_PASS=passw" >> .env
  echo "DREVOPS_REDIS_ENABLED=1" >> .env
  echo "LAGOON_ENVIRONMENT_TYPE=development" >> .env

  substep "Validate configuration"
  run docker compose -f docker-compose.yml config
  assert_success

  substep "Compare with fixture"
  prepare_docker_compose_fixture

  docker compose -f docker-compose.yml config --format json > docker-compose.actual.json
  echo "" >> docker-compose.actual.json

  assert_files_equal docker-compose.actual.json docker-compose.env_mod.json
}

prepare_docker_compose() {
  cp "${CUR_DIR}/docker-compose.yml" docker-compose.yml

  unset GITHUB_TOKEN

  # In order for tests to pass locally and in CI, we need to replicate the
  # environment locally to be the same as in CI.
  export CI=true

  # Process codebase to run in CI
  sed -i -e "/###/d" docker-compose.yml && sed -i -e "s/##//" docker-compose.yml
}

prepare_docker_compose_fixture() {
  cp "${CUR_DIR}/scripts/drevops/tests/bats/fixtures/docker-compose.env.json" docker-compose.env.json
  cp "${CUR_DIR}/scripts/drevops/tests/bats/fixtures/docker-compose.env_mod.json" docker-compose.env_mod.json
  cp "${CUR_DIR}/scripts/drevops/tests/bats/fixtures/docker-compose.noenv.json" docker-compose.noenv.json
  replace_string_content "FIXTURE_CUR_DIR" "${CURRENT_PROJECT_DIR}" "${CURRENT_PROJECT_DIR}"

  # Replace symlink /private paths in MacOS.
  replace_string_content "/private/var/" "/var/" "${CURRENT_PROJECT_DIR}"
}
