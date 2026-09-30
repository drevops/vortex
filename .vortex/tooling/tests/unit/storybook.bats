#!/usr/bin/env bats
##
# Unit tests for the 'vortex-storybook' script.
#
# The mock side effects expand when the mock runs, not when the step is defined,
# so they are single-quoted.
#
#shellcheck disable=SC2030,SC2031,SC2034,SC2016

load ../_helper.bash

# Clears the Storybook variables inherited from the shell running the suite.
storybook_env_reset() {
  unset VORTEX_STORYBOOK_DIR VORTEX_STORYBOOK_STORIES_PATTERN VORTEX_STORYBOOK_DEV_SCRIPT VORTEX_STORYBOOK_BUILD_SCRIPT VORTEX_STORYBOOK_PORT VORTEX_HOST_STORYBOOK_PORT STORYBOOK_SERVER_URL VORTEX_STORYBOOK_BUILD_SERVER_URL VORTEX_STORYBOOK_LIBRARY_PATH

  rm ./.env && touch ./.env

  export DRUPAL_THEME=your_site_theme
  export LOCALDEV_URL=star-wars.docker.amazee.io

  create_global_command_wrapper "vendor/bin/drush"
}

@test "Storybook: generate stories" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  storybook_env_reset

  declare -a STEPS=(
    "@drush -y storybook:generate-all-stories --omit-server-url --force"

    "Generating stories."
    "Generated stories."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-storybook stories
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Storybook: generate and watch stories" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  storybook_env_reset

  create_global_command_wrapper "web/themes/custom/your_site_theme/node_modules/.bin/chokidar" "chokidar"

  declare -a STEPS=(
    "@drush -y storybook:generate-all-stories --omit-server-url --force"
    '@chokidar ./web/themes/custom/your_site_theme/components/**/*.stories.twig -c ./vendor/bin/drush -y storybook:generate-all-stories --omit-server-url --force # 0 #  # echo "${SHELL}" >./watcher_shell.txt'

    "Generated stories."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-storybook stories --watch
  assert_success

  steps_run "assert" "${mocks[@]}"

  # chokidar-cli needs SHELL in its environment to run the command.
  assert_file_contains "./watcher_shell.txt" "/bin/sh"

  popd >/dev/null || exit 1
}

@test "Storybook: build the library" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  storybook_env_reset

  # A URL set for the development server must not reach the static build.
  export STORYBOOK_SERVER_URL=http://localhost:6006

  declare -a STEPS=(
    "@drush -y storybook:generate-all-stories --omit-server-url --force"
    '@npm --prefix=./web/themes/custom/your_site_theme run storybook-build # 0 #  # echo "[${STORYBOOK_SERVER_URL}]" >./build_server_url.txt'

    "Started Storybook build."
    "Generated stories."
    "Building the Storybook application."
    "Built the Storybook application."
    "Storybook library: http://star-wars.docker.amazee.io/storybook"
    "Finished Storybook build."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-storybook build
  assert_success

  steps_run "assert" "${mocks[@]}"

  assert_file_contains "./build_server_url.txt" "[]"

  popd >/dev/null || exit 1
}

@test "Storybook: build the library from custom locations" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  storybook_env_reset

  export VORTEX_STORYBOOK_DIR=./frontend/storybook
  export VORTEX_STORYBOOK_BUILD_SCRIPT=build-library
  export VORTEX_STORYBOOK_BUILD_SERVER_URL=https://cms.example.com
  export VORTEX_STORYBOOK_LIBRARY_PATH=/components
  export LOCALDEV_URL="https://star-wars.docker.amazee.io, star-wars-alt.docker.amazee.io"

  declare -a STEPS=(
    "@drush -y storybook:generate-all-stories --omit-server-url --force"
    '@npm --prefix=./frontend/storybook run build-library # 0 #  # echo "[${STORYBOOK_SERVER_URL}]" >./build_server_url.txt'

    "Built the Storybook application."
    "Storybook library: https://star-wars.docker.amazee.io/components"
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-storybook build
  assert_success

  steps_run "assert" "${mocks[@]}"

  assert_file_contains "./build_server_url.txt" "[https://cms.example.com]"

  popd >/dev/null || exit 1
}

@test "Storybook: start the development server" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  storybook_env_reset

  export VORTEX_HOST_STORYBOOK_PORT=55197

  # The watcher runs in the background and is stopped on exit; the watch test
  # covers its arguments.
  mkdir -p "./web/themes/custom/your_site_theme/node_modules/.bin"
  printf '#!/usr/bin/env bash\nexit 0\n' >"./web/themes/custom/your_site_theme/node_modules/.bin/chokidar"
  chmod +x "./web/themes/custom/your_site_theme/node_modules/.bin/chokidar"

  declare -a STEPS=(
    "@pkill -f node .*storybook dev # 1"
    "@pkill -f node .*chokidar .*storybook:generate-all-stories # 1"
    "@drush -y storybook:generate-all-stories --omit-server-url --force"
    '@npm --prefix=./web/themes/custom/your_site_theme run storybook -- --port 6006 --quiet --ci # 0 #  # echo "${STORYBOOK_SERVER_URL}" >./dev_server_url.txt'
    "@curl -s -o /dev/null http://localhost:6006 # 0"

    "Started Storybook development server."
    "Generated stories."
    "Starting the Storybook development server."
    "Storybook is ready at http://localhost:55197"

    "- Storybook development server did not start."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-storybook dev
  assert_success

  steps_run "assert" "${mocks[@]}"

  assert_file_contains "./dev_server_url.txt" "http://star-wars.docker.amazee.io"

  popd >/dev/null || exit 1
}

@test "Storybook: start the development server with custom settings" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  storybook_env_reset

  export VORTEX_STORYBOOK_PORT=7007
  export VORTEX_STORYBOOK_DEV_SCRIPT=dev
  export STORYBOOK_SERVER_URL=https://cms.example.com

  mkdir -p "./web/themes/custom/your_site_theme/node_modules/.bin"
  printf '#!/usr/bin/env bash\nexit 0\n' >"./web/themes/custom/your_site_theme/node_modules/.bin/chokidar"
  chmod +x "./web/themes/custom/your_site_theme/node_modules/.bin/chokidar"

  declare -a STEPS=(
    "@pkill -f node .*storybook dev # 1"
    "@pkill -f node .*chokidar .*storybook:generate-all-stories # 1"
    "@drush -y storybook:generate-all-stories --omit-server-url --force"
    '@npm --prefix=./web/themes/custom/your_site_theme run dev -- --port 7007 --quiet --ci --smoke-test # 0 #  # echo "${STORYBOOK_SERVER_URL}" >./dev_server_url.txt'
    "@curl -s -o /dev/null http://localhost:7007 # 0"

    "Storybook is ready at http://localhost:7007"
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-storybook dev --smoke-test
  assert_success

  steps_run "assert" "${mocks[@]}"

  assert_file_contains "./dev_server_url.txt" "https://cms.example.com"

  popd >/dev/null || exit 1
}

@test "Storybook: unknown action" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  storybook_env_reset

  run .vortex/tooling/src/vortex-storybook publish
  assert_failure

  assert_output_contains "Unknown action 'publish'. Use 'dev', 'build' or 'stories'."

  popd >/dev/null || exit 1
}
