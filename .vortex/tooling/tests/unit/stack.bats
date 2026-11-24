#!/usr/bin/env bats
##
# Unit tests for the 'scripts/vortex-stack.sh' script.
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Normalized 'docker compose config' output with the Pygmy network and agent.
CONFIG_PYGMY='name: star_wars\nservices:\n  cli:\n    image: star_wars\n    volumes_from:\n      - container:amazeeio-ssh-agent\n    networks:\n      default: null\n  nginx:\n    networks:\n      amazeeio-network: null\n      default: null\n    user: "1000"\nnetworks:\n  amazeeio-network:\n    name: amazeeio-network\n    external: true\n  default:\n    name: star_wars_default\nvolumes:\n  app:\n    name: star_wars_app\n'

# Normalized 'docker compose config' output with the Pygmy lines removed, as
# in CI.
CONFIG_CI='name: star_wars\nservices:\n  cli:\n    image: star_wars\n    networks:\n      default: null\nnetworks:\n  default:\n    name: star_wars_default\n'

# Normalized 'docker compose config' output with an external network declared
# under a key that differs from its name.
CONFIG_NAMED='name: star_wars\nservices:\n  nginx:\n    networks:\n      proxy: null\nnetworks:\n  proxy:\n    name: shared-proxy\n    external: true\n'

FORMAT_CONTAINER='{{range .NetworkSettings.Networks}}{{.NetworkID}} {{end}}|{{range .Mounts}}{{.Name}} {{end}}'
FORMAT_MOUNTS='{{range .Mounts}}{{.Name}} {{end}}'

@test "Stack: up passes arguments to Docker Compose when no container is stale" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_PYGMY}"
    "@docker network inspect amazeeio-network # 0"
    "@docker inspect --format {{.State.Running}} amazeeio-ssh-agent # true"
    "@docker compose ps --all --format {{.Service}}:{{.ID}} # cli:c1\nnginx:n1"
    "@docker inspect --format ${FORMAT_CONTAINER} c1 # net-default |vol-agent-1"
    "@docker network inspect net-default # 0"
    "@docker inspect --format ${FORMAT_MOUNTS} amazeeio-ssh-agent # vol-agent-1"
    "@docker inspect --format ${FORMAT_CONTAINER} n1 # net-default net-pygmy |"
    "@docker network inspect net-default # 0"
    "@docker network inspect net-pygmy # 0"
    "@docker compose up --detach --build cli # 0"

    "- Recreating containers"
    "- is not running"
    "- does not exist"
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh up --build cli
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: up runs without checks when the configuration has no external dependencies" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_CI}"
    "@docker compose ps --all --format {{.Service}}:{{.ID}} # 0"
    "@docker compose up --detach # 0"

    "- Recreating containers"
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh up
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: up leaves configuration errors to Docker Compose" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # 1"
    "@docker compose ps --all --format {{.Service}}:{{.ID}} # 0"
    "@docker compose up --detach # 1"

    "- Required Docker resources are missing."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh up
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: up fails before creating containers when the external network is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_PYGMY}"
    "@docker network inspect amazeeio-network # 1"
    "@docker inspect --format {{.State.Running}} amazeeio-ssh-agent # true"

    "Network 'amazeeio-network' is declared as external but does not exist."
    "Required Docker resources are missing. Start the tool that provides them, such as Pygmy with 'pygmy up', and run this command again."
    "- Container 'amazeeio-ssh-agent'"
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh up
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: up checks an external network by its name rather than its key" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_NAMED}"
    "@docker network inspect shared-proxy # 1"

    "Network 'shared-proxy' is declared as external but does not exist."
    "Required Docker resources are missing."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh up
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: up fails before creating containers when the volumes_from container is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_PYGMY}"
    "@docker network inspect amazeeio-network # 0"
    "@docker inspect --format {{.State.Running}} amazeeio-ssh-agent # 1"

    "Container 'amazeeio-ssh-agent' that provides volumes to service 'cli' does not exist."
    "Required Docker resources are missing. Start the tool that provides them, such as Pygmy with 'pygmy up', and run this command again."
    "- Network 'amazeeio-network'"
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh up
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: up continues with a note when the volumes_from container is stopped" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_PYGMY}"
    "@docker network inspect amazeeio-network # 0"
    "@docker inspect --format {{.State.Running}} amazeeio-ssh-agent # false"
    "@docker compose ps --all --format {{.Service}}:{{.ID}} # 0"
    "@docker compose up --detach # 0"

    "Container 'amazeeio-ssh-agent' that provides volumes to service 'cli' is not running, so these volumes may be incomplete."
    "- Required Docker resources are missing."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh up
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: up recreates a container attached to a removed network" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_PYGMY}"
    "@docker network inspect amazeeio-network # 0"
    "@docker inspect --format {{.State.Running}} amazeeio-ssh-agent # true"
    "@docker compose ps --all --format {{.Service}}:{{.ID}} # cli:c1\nnginx:n1"
    "@docker inspect --format ${FORMAT_CONTAINER} c1 # net-default |vol-agent-1"
    "@docker network inspect net-default # 0"
    "@docker inspect --format ${FORMAT_MOUNTS} amazeeio-ssh-agent # vol-agent-1"
    "@docker inspect --format ${FORMAT_CONTAINER} n1 # net-default net-pygmy-old |"
    "@docker network inspect net-default # 0"
    "@docker network inspect net-pygmy-old # 1"
    "@docker compose up --no-start --no-deps --force-recreate nginx # 0"
    "@docker compose up --detach # 0"

    "Service 'nginx' is attached to a network that no longer exists."
    "Recreating containers for services: nginx."
    "Recreated containers for services: nginx."
    "- Service 'cli'"
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh up
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: up recreates a container that lacks the current volumes of its volumes_from container" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_PYGMY}"
    "@docker network inspect amazeeio-network # 0"
    "@docker inspect --format {{.State.Running}} amazeeio-ssh-agent # true"
    "@docker compose ps --all --format {{.Service}}:{{.ID}} # cli:c1\nnginx:n1"
    "@docker inspect --format ${FORMAT_CONTAINER} c1 # net-default |  vol-agent-old "
    "@docker network inspect net-default # 0"
    "@docker inspect --format ${FORMAT_MOUNTS} amazeeio-ssh-agent # vol-agent-new"
    "@docker inspect --format ${FORMAT_CONTAINER} n1 # net-default net-pygmy |"
    "@docker network inspect net-default # 0"
    "@docker network inspect net-pygmy # 0"
    "@docker compose up --no-start --no-deps --force-recreate cli # 0"
    "@docker compose up --detach # 0"

    "Service 'cli' is not using the current volumes of container 'amazeeio-ssh-agent'."
    "Recreating containers for services: cli."
    "Recreated containers for services: cli."
    "- Service 'nginx'"
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh up
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: up skips the stale container check when containers are force-recreated" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_PYGMY}"
    "@docker network inspect amazeeio-network # 0"
    "@docker inspect --format {{.State.Running}} amazeeio-ssh-agent # true"
    "@docker compose ps --all --format {{.Service}}:{{.ID}} # cli:c1\nnginx:n1"
    "@docker compose up --detach --build --force-recreate # 0"

    "- Recreating containers"
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh up --build --force-recreate
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: start starts existing containers" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_CI}"
    "@docker compose ps --all --format {{.Service}}:{{.ID}} # cli:c1"
    "@docker inspect --format ${FORMAT_CONTAINER} c1 # net-default |"
    "@docker network inspect net-default # 0"
    "@docker compose start cli # 0"

    "- No containers to start."
    "- Recreating containers"
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh start cli
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: start creates containers when the project has none" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_CI}"
    "@docker compose ps --all --format {{.Service}}:{{.ID}} # 0"
    "@docker compose up --detach cli # 0"

    "No containers to start. Creating them instead."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh start cli
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: start recreates every container attached to a removed network before starting" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "@docker compose config # ${CONFIG_CI}"
    "@docker compose ps --all --format {{.Service}}:{{.ID}} # cli:c1\ndatabase:d1\ndatabase:d2"
    "@docker inspect --format ${FORMAT_CONTAINER} c1 # net-old |"
    "@docker network inspect net-old # 1"
    "@docker inspect --format ${FORMAT_CONTAINER} d1 # net-old |"
    "@docker network inspect net-old # 1"
    "@docker compose up --no-start --no-deps --force-recreate cli database # 0"
    "@docker compose start # 0"

    "Service 'cli' is attached to a network that no longer exists."
    "Service 'database' is attached to a network that no longer exists."
    "Recreating containers for services: cli database."
    "Recreated containers for services: cli database."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/vortex-stack.sh start
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Stack: fails for an unknown subcommand" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker="$(mock_command "docker")"

  run ./scripts/vortex-stack.sh restart
  assert_failure
  assert_output_contains "Unknown subcommand 'restart'. Use 'up' or 'start'."
  assert_equal "$(mock_get_call_num "${mock_docker}")" 0

  run ./scripts/vortex-stack.sh
  assert_failure
  assert_output_contains "Unknown subcommand ''. Use 'up' or 'start'."
  assert_equal "$(mock_get_call_num "${mock_docker}")" 0

  popd >/dev/null || exit 1
}
