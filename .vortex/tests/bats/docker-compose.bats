#!/usr/bin/env bats
#
# Test for docker compose format and default variables.
#
# Run with `UPDATE_FIXTURES=1` to update docker-compose.*.json fixtures.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2016

load _helper.bash

@test "Docker Compose: default without .env" {
  prepare_docker_compose

  substep "Validate configuration"
  run docker compose -f docker-compose.yml config
  assert_success

  substep "Compare with fixture"
  prepare_docker_compose_fixtures

  docker compose -f docker-compose.yml config --format json >docker-compose.actual.json
  process_docker_compose_json docker-compose.actual.json
  update_docker_compose_fixture "${PWD}"/docker-compose.actual.json docker-compose.noenv.json

  assert_files_equal docker-compose.actual.json docker-compose.noenv.json
}

@test "Docker Compose: default with .env" {
  prepare_docker_compose

  cp "${ROOT_DIR}/.env" .env

  substep "Validate configuration"
  run docker compose -f docker-compose.yml config
  assert_success

  substep "Compare with fixture"
  prepare_docker_compose_fixtures

  docker compose -f docker-compose.yml config --format json >docker-compose.actual.json
  process_docker_compose_json docker-compose.actual.json
  update_docker_compose_fixture "${PWD}"/docker-compose.actual.json docker-compose.env.json

  assert_files_equal docker-compose.actual.json docker-compose.env.json
}

@test "Docker Compose: default with .env modified" {
  prepare_docker_compose

  cp "${ROOT_DIR}/.env" .env

  echo "COMPOSE_PROJECT_NAME=the_matrix" >>.env
  echo "VORTEX_WEBROOT=docroot" >>.env
  echo "VORTEX_DB_IMAGE=myorg/my_db_image" >>.env
  echo "XDEBUG_ENABLE=1" >>.env
  echo "DRUPAL_SHIELD_USER=jane" >>.env
  echo "DRUPAL_SHIELD_PASS=passw" >>.env
  echo "DRUPAL_REDIS_ENABLED=1" >>.env
  echo "LAGOON_ENVIRONMENT_TYPE=development" >>.env

  substep "Validate configuration"
  run docker compose -f docker-compose.yml config
  assert_success

  substep "Compare with fixture"
  prepare_docker_compose_fixtures

  docker compose -f docker-compose.yml config --format json >docker-compose.actual.json
  process_docker_compose_json docker-compose.actual.json
  update_docker_compose_fixture "${PWD}"/docker-compose.actual.json docker-compose.env_mod.json

  assert_files_equal docker-compose.actual.json docker-compose.env_mod.json
}

@test "Docker Compose: default with .env and .env.local" {
  prepare_docker_compose

  cp "${ROOT_DIR}/.env" .env
  cp "${ROOT_DIR}/.env.local.default" .env.local

  substep "Validate configuration"
  run docker compose -f docker-compose.yml config
  assert_success

  substep "Compare with fixture"
  prepare_docker_compose_fixtures

  docker compose -f docker-compose.yml config --format json >docker-compose.actual.json
  process_docker_compose_json docker-compose.actual.json
  update_docker_compose_fixture "${PWD}"/docker-compose.actual.json docker-compose.env_local.json

  assert_files_equal docker-compose.actual.json docker-compose.env_local.json
}

# Prepare current docker compose file for testing.
prepare_docker_compose() {
  cp "${ROOT_DIR}/docker-compose.yml" docker-compose.yml

  unset GITHUB_TOKEN

  # In order for tests to pass locally and in CI, we need to replicate the
  # environment locally to be the same as in CI.
  export CI=true

  # Process codebase to run in CI
  sed -i -e "/###/d" docker-compose.yml && sed -i -e "s/##//" docker-compose.yml
}

# Prepare fixtures docker-compose for testing.
prepare_docker_compose_fixtures() {
  cp "${ROOT_DIR}/.vortex/tests/bats/fixtures/docker-compose.env.json" docker-compose.env.json
  cp "${ROOT_DIR}/.vortex/tests/bats/fixtures/docker-compose.env_mod.json" docker-compose.env_mod.json
  cp "${ROOT_DIR}/.vortex/tests/bats/fixtures/docker-compose.noenv.json" docker-compose.noenv.json
  cp "${ROOT_DIR}/.vortex/tests/bats/fixtures/docker-compose.env_local.json" docker-compose.env_local.json
  replace_string_content "FIXTURE_CUR_DIR" "${CURRENT_PROJECT_DIR}" "${CURRENT_PROJECT_DIR}"

  # Replace symlink /private paths in MacOS.
  replace_string_content "/private/var/" "/var/" "${CURRENT_PROJECT_DIR}"
}

process_docker_compose_json() {
  local from="${1}"
  local to="${2:-${1}}"

  # Sort all values recursively by key in the alphabetical order to avoid
  # sorting issues between Docker Compose versions.
  php -r "
    \$data = json_decode(\$argv[1], true);
    function ksort_multi(&\$array) {
      foreach (\$array as &\$value) {
        if (is_array(\$value)) {
          ksort_multi(\$value);
        }
      }
      ksort(\$array);
    }
    ksort_multi(\$data);

    # Remove YAML anchors starting with 'x-'.
    \$data = array_filter(\$data, function(\$key) {
      return strpos(\$key, 'x-') !== 0;
    }, ARRAY_FILTER_USE_KEY);

    array_walk_recursive(\$data, function (&\$value) {
      if (\$value !== null && preg_match('/:\d+\.\d+(\.\d+)?/', \$value)) {
        \$value = preg_replace('/:\d+\.\d+(?:\.\d+)?/', ':VERSION', \$value);
      }
    });

    array_walk_recursive(\$data, function (&\$value) {
      if (\$value !== null && str_contains(\$value, \"$HOME\")) {
        \$value = str_replace(\"$HOME\", 'HOME', \$value);
      }
    });

    \$data = json_encode(\$data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    print \$data;
  " "$(cat "${from}")" "${CURRENT_PROJECT_DIR}" >"${to}"
  echo "" >>"${to}"
}

# Helper to update fixtures.
# Using the test system instead of a standlone script to avoid duplication of
# file processing logic.
# Run the tests with UPDATE_FIXTURES=1 to update the fixtures.
update_docker_compose_fixture() {
  if [ -n "${UPDATE_FIXTURES:-}" ]; then
    step "Updating fixtures"
    replace_string_content "${CURRENT_PROJECT_DIR}" "FIXTURE_CUR_DIR" "${CURRENT_PROJECT_DIR}"
    cp -Rf "${1}" "${ROOT_DIR}/.vortex/tests/bats/fixtures/${2}"
  fi
}
