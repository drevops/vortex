#!/usr/bin/env bats
##
# Unit tests for the 'vortex-doctor' script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Runs the SSH check on its own: the helper disables every other host check
# except the containers check. The MINIMAL preset overwrites the per-check
# flags, so a preset inherited from the environment is reset.
fixture_variables() {
  export VORTEX_DOCTOR_CHECK_MINIMAL=0
  export VORTEX_DOCTOR_CHECK_CONTAINERS=0
  export VORTEX_DOCTOR_CHECK_SSH=1
  export VORTEX_SSH_FILE="/home/user/.ssh/id_rsa"
}

@test "doctor: SSH key available in the CLI container passes the SSH check" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  declare -a STEPS=(
    "@pygmy status # 0 # [*] amazeeio-ssh-agent: Running as container amazeeio-ssh-agent\n4096 SHA256:fingerprint ${VORTEX_SSH_FILE} (RSA)"
    '@docker compose exec -T cli bash -c grep "^/dev" /etc/mtab | grep -q /tmp/amazeeio_ssh-agent # 0'
    "@docker compose exec -T cli bash -c ssh-add -l >/dev/null 2>&1 # 0"
    "SSH key is available within the CLI container."
    "- SSH key is not added to pygmy."
    "- SSH key volume is not mounted into CLI container."
    "- SSH key was not added to the container."
    "All required checks have passed."
  )
  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-doctor
  assert_success
  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "doctor: unreachable SSH agent in the CLI container recommends recreating the CLI container" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  declare -a STEPS=(
    "@pygmy status # 0 # [*] amazeeio-ssh-agent: Running as container amazeeio-ssh-agent\n4096 SHA256:fingerprint ${VORTEX_SSH_FILE} (RSA)"
    '@docker compose exec -T cli bash -c grep "^/dev" /etc/mtab | grep -q /tmp/amazeeio_ssh-agent # 0'
    "@docker compose exec -T cli bash -c ssh-add -l >/dev/null 2>&1 # 2"
    "SSH key was not added to the container. Run 'ahoy up --no-deps --force-recreate cli'."
    "- Run 'pygmy restart'."
    "- SSH key is available within the CLI container."
    "- All required checks have passed."
  )
  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-doctor
  assert_failure
  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "doctor: SSH key missing from Pygmy recommends restarting Pygmy and recreating the CLI container" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  declare -a STEPS=(
    "@pygmy status # 0 # [*] amazeeio-ssh-agent: Running as container amazeeio-ssh-agent\nThe agent has no identities."
    '@docker compose exec -T cli bash -c grep "^/dev" /etc/mtab | grep -q /tmp/amazeeio_ssh-agent # 0'
    "SSH key is not added to pygmy."
    "The SSH key will not be available in the CLI container. Run 'pygmy restart' and then 'ahoy up --no-deps --force-recreate cli'."
    "- and then 'ahoy up'."
    "- SSH key is available within the CLI container."
    "- SSH key was not added to the container."
    "All required checks have passed."
  )
  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-doctor
  assert_success
  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "doctor: SSH key volume missing from the CLI container recommends mounting it" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  declare -a STEPS=(
    "@pygmy status # 0 # [*] amazeeio-ssh-agent: Running as container amazeeio-ssh-agent\n4096 SHA256:fingerprint ${VORTEX_SSH_FILE} (RSA)"
    '@docker compose exec -T cli bash -c grep "^/dev" /etc/mtab | grep -q /tmp/amazeeio_ssh-agent # 1'
    "SSH key volume is not mounted into CLI container."
    'Make sure that your "docker-compose.yml" has the following lines for CLI service:'
    "volumes_from:"
    "container:amazeeio-ssh-agent"
    "After adding these lines, run 'ahoy up'."
    "- SSH key is available within the CLI container."
    "- SSH key was not added to the container."
    "All required checks have passed."
  )
  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-doctor
  assert_success
  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "doctor: disabled SSH check skips the SSH key checks" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_DOCTOR_CHECK_SSH=0

  mock_pygmy=$(mock_command "pygmy")
  mock_docker=$(mock_command "docker")

  run .vortex/tooling/src/vortex-doctor
  assert_success
  assert_output_contains "All required checks have passed."
  assert_output_not_contains "SSH key"
  assert_equal "0" "$(mock_get_call_num "${mock_pygmy}")"
  assert_equal "0" "$(mock_get_call_num "${mock_docker}")"

  popd >/dev/null || exit 1
}

# Runs the Pygmy check on its own.
fixture_pygmy_variables() {
  export VORTEX_DOCTOR_CHECK_MINIMAL=0
  export VORTEX_DOCTOR_CHECK_CONTAINERS=0
  export VORTEX_DOCTOR_CHECK_PYGMY=1
}

@test "doctor: running Pygmy passes the Pygmy check without querying Pygmy" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_pygmy_variables

  mock_pygmy=$(mock_command "pygmy")

  declare -a STEPS=(
    "@docker network inspect amazeeio-network # 0"
    "@docker inspect --format {{.Name}} {{.State.Running}} amazeeio-ssh-agent amazeeio-mailhog amazeeio-haproxy amazeeio-dnsmasq # /amazeeio-ssh-agent true\n/amazeeio-mailhog true\n/amazeeio-haproxy true\n/amazeeio-dnsmasq true"
    "Pygmy is running."
    "All required checks have passed."
  )
  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-doctor
  assert_success
  steps_run "assert" "${mocks[@]}"
  assert_equal "0" "$(mock_get_call_num "${mock_pygmy}")"

  popd >/dev/null || exit 1
}

@test "doctor: missing Pygmy network fails the Pygmy check" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_pygmy_variables

  declare -a STEPS=(
    "@docker network inspect amazeeio-network # 1"
    "Pygmy network amazeeio-network does not exist. Run 'pygmy up' to fix."
    "- Pygmy is running."
    "- All required checks have passed."
  )
  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-doctor
  assert_failure
  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "doctor: stopped Pygmy service fails the Pygmy check" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_pygmy_variables

  declare -a STEPS=(
    "@docker network inspect amazeeio-network # 0"
    "@docker inspect --format {{.Name}} {{.State.Running}} amazeeio-ssh-agent amazeeio-mailhog amazeeio-haproxy amazeeio-dnsmasq # /amazeeio-ssh-agent true\n/amazeeio-mailhog true\n/amazeeio-haproxy false\n/amazeeio-dnsmasq true"
    "Pygmy service amazeeio-haproxy is not running. Run 'pygmy up' or 'pygmy restart' to fix."
    "- Pygmy is running."
  )
  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-doctor
  assert_failure
  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "doctor: missing Pygmy service container fails the Pygmy check" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_pygmy_variables

  declare -a STEPS=(
    "@docker network inspect amazeeio-network # 0"
    "@docker inspect --format {{.Name}} {{.State.Running}} amazeeio-ssh-agent amazeeio-mailhog amazeeio-haproxy amazeeio-dnsmasq # 1 # /amazeeio-mailhog true\n/amazeeio-haproxy true\n/amazeeio-dnsmasq true"
    "Pygmy service amazeeio-ssh-agent is not running. Run 'pygmy up' or 'pygmy restart' to fix."
    "- Pygmy is running."
  )
  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-doctor
  assert_failure
  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
