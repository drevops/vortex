#!/usr/bin/env bash
#
# Check project requirements or print info.
#
# doctor.sh - check project requirements.
# doctor.sh info - show system information.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Check minimal Doctor requirements.
VORTEX_DOCTOR_CHECK_MINIMAL="${VORTEX_DOCTOR_CHECK_MINIMAL:-0}"

# Check pre-flight Doctor requirements.
VORTEX_DOCTOR_CHECK_PREFLIGHT="${VORTEX_DOCTOR_CHECK_PREFLIGHT:-0}"

if [ "${VORTEX_DOCTOR_CHECK_MINIMAL}" = "1" ]; then
  VORTEX_DOCTOR_CHECK_PORT=0
  VORTEX_DOCTOR_CHECK_PYGMY=0
  VORTEX_DOCTOR_CHECK_SSH=0
  VORTEX_DOCTOR_CHECK_WEBSERVER=0
  VORTEX_DOCTOR_CHECK_BOOTSTRAP=0
fi

if [ "${VORTEX_DOCTOR_CHECK_PREFLIGHT}" = "1" ]; then
  VORTEX_DOCTOR_CHECK_TOOLS="${VORTEX_DOCTOR_CHECK_TOOLS:-1}"
  VORTEX_DOCTOR_CHECK_PORT="${VORTEX_DOCTOR_CHECK_PORT:-1}"
  VORTEX_DOCTOR_CHECK_PYGMY="${VORTEX_DOCTOR_CHECK_PYGMY:-1}"
  VORTEX_DOCTOR_CHECK_CONTAINERS="${VORTEX_DOCTOR_CHECK_CONTAINERS:-0}"
  VORTEX_DOCTOR_CHECK_SSH="${VORTEX_DOCTOR_CHECK_SSH:-0}"
  VORTEX_DOCTOR_CHECK_WEBSERVER="${VORTEX_DOCTOR_CHECK_WEBSERVER:-0}"
  VORTEX_DOCTOR_CHECK_BOOTSTRAP="${VORTEX_DOCTOR_CHECK_BOOTSTRAP:-0}"
fi

# Check Doctor requirements for presence of tools.
VORTEX_DOCTOR_CHECK_TOOLS="${VORTEX_DOCTOR_CHECK_TOOLS:-1}"

# Check Doctor requirements for port of availability.
VORTEX_DOCTOR_CHECK_PORT="${VORTEX_DOCTOR_CHECK_PORT:-1}"

# Check Doctor requirements for Pygmy of availability.
VORTEX_DOCTOR_CHECK_PYGMY="${VORTEX_DOCTOR_CHECK_PYGMY:-1}"

# Check Doctor requirements for container status.
VORTEX_DOCTOR_CHECK_CONTAINERS="${VORTEX_DOCTOR_CHECK_CONTAINERS:-1}"

# Check Doctor requirements for SSH key.
VORTEX_DOCTOR_CHECK_SSH="${VORTEX_DOCTOR_CHECK_SSH:-1}"

# Check Doctor requirements for webserver status.
VORTEX_DOCTOR_CHECK_WEBSERVER="${VORTEX_DOCTOR_CHECK_WEBSERVER:-1}"

# Check Doctor requirements for application bootstrap status.
VORTEX_DOCTOR_CHECK_BOOTSTRAP="${VORTEX_DOCTOR_CHECK_BOOTSTRAP:-1}"

# Default SSH key file.
VORTEX_SSH_FILE="${VORTEX_SSH_FILE:-${HOME}/.ssh/id_rsa}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
warn() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[33m[WARN] %s\033[0m\n" "${1}" || printf "[WARN] %s\n" "${1}"; }
# @formatter:on

for cmd in docker pygmy ahoy; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

#
# Main entry point.
#
main() {
  [ "${1:-}" = "info" ] && system_info && exit

  info "Checking project requirements"

  if [ "${VORTEX_DOCTOR_CHECK_TOOLS}" = "1" ]; then
    [ "$(command_exists docker)" = "1" ] && fail "Please install Docker (https://www.docker.com/get-started)." && exit 1
    [ "$(command_exists docker compose)" = "1" ] && fail "Please install docker compose (https://docs.docker.com/compose/install/)." && exit 1
    [ "$(command_exists pygmy)" = "1" ] && fail "Please install Pygmy (https://pygmy.readthedocs.io/)." && exit 1
    [ "$(command_exists ahoy)" = "1" ] && fail "Please install Ahoy (https://ahoy-cli.readthedocs.io/)." && exit 1
    pass "All required tools are present."
  fi

  if [ "${VORTEX_DOCTOR_CHECK_PORT}" = "1" ] && [ "${OSTYPE}" != "linux-gnu" ]; then
    if lsof -i :80 | grep -v 'CLOSED' | grep 'LISTEN' | grep -vq 'om.docke'; then
      fail "Port 80 is occupied by a service other than Docker. Stop this service and run 'pygmy up'."
      exit 1
    fi
    pass "Port 80 is available."
  fi

  if [ "${VORTEX_DOCTOR_CHECK_PYGMY}" = "1" ]; then
    pygmy_status="$(pygmy status | tr -d '\000')"

    pygmy_services=()
    pygmy_services+=("amazeeio-ssh-agent")
    pygmy_services+=("amazeeio-mailhog")
    pygmy_services+=("amazeeio-haproxy")
    pygmy_services+=("amazeeio-dnsmasq")

    for pygmy_service in "${pygmy_services[@]}"; do
      if ! echo "${pygmy_status}" | grep -q "${pygmy_service}: Running"; then
        fail "Pygmy service ${pygmy_service} is not running. Run 'pygmy up' or 'pygmy restart' to fix."
        exit 1
      fi
    done
    pass "Pygmy is running."
  fi

  # Check that the stack is running.
  if [ "${VORTEX_DOCTOR_CHECK_CONTAINERS}" = "1" ]; then
    container_services=(cli php nginx database)
    for container_service in "${container_services[@]}"; do
      if ! docker compose ps --status=running --services | grep -q "${container_service}"; then
        fail "${container_service} container is not running."
        echo "      Run 'ahoy up'."
        echo "      Run 'ahoy logs ${container_service}' to see error logs."
        exit 1
      fi
    done
    pass "All containers are running"
  fi

  if [ "${VORTEX_DOCTOR_CHECK_SSH}" = "1" ]; then
    # SSH key injection is required to access Lagoon services from within
    # containers. For example, to connect to a production environment to run
    # a drush script.
    # Pygmy makes this possible in the following way:
    # 1. Pygmy starts the `amazeeio/ssh-agent` container with a volume `/tmp/amazeeio_ssh-agent`
    # 2. Pygmy adds a default SSH key from the host into this volume.
    # 3. `docker-compose.yml` should have volume inclusion specified for the CLI container:
    #    ```
    #    volumes_from:
    #      - container:amazeeio-ssh-agent
    #    ```
    # 4. When the CLI container starts, the volume is mounted and an entrypoint
    #    script loads the SSH key into an agent.
    #    @see https://github.com/uselagoon/lagoon-images/blob/main/images/php-cli/entrypoints/10-ssh-agent.sh
    #
    # Running `ssh-add -L` within the CLI container should show that the SSH key
    # was correctly loaded.
    #
    # As a rule of thumb, one must restart the CLI container after restarting
    # Pygmy ONLY if the SSH key was not loaded in Pygmy before the stack starts.
    # No need to restart the CLI container if the key was added, but Pygmy was
    # restarted - the volume mount will be retained, and the key will still be
    # available in the CLI container.

    ssh_key_added_to_pygmy=0
    ssh_key_volume_mounted=0

    # Check that the key is injected into pygmy ssh-agent container.
    if ! pygmy status 2>&1 | grep -q "${VORTEX_SSH_FILE}"; then
      warn "SSH key is not added to pygmy."
      note "The SSH key will not be available in CLI container. Run 'pygmy restart' and then 'ahoy up'"
    else
      ssh_key_added_to_pygmy=1
    fi

    # Check that the volume is mounted into CLI container.
    if ! docker compose exec -T cli bash -c 'grep "^/dev" /etc/mtab | grep -q /tmp/amazeeio_ssh-agent'; then
      warn "SSH key volume is not mounted into CLI container."
      note 'Make sure that your "docker-compose.yml" has the following lines for CLI service:'
      note "  volumes_from:"
      note "    - container:amazeeio-ssh-agent"
      note "After adding these lines, run 'ahoy up'."
    else
      ssh_key_volume_mounted=1
    fi

    # Check that ssh key is available in the container, but only if the above checks passed.
    if [ "${ssh_key_added_to_pygmy}" = "1" ] && [ "${ssh_key_volume_mounted}" = "1" ]; then
      if ! docker compose exec -T cli bash -c "ssh-add -L | grep -q 'ssh-rsa'"; then
        fail "SSH key was not added into container. Run 'pygmy restart'."
      else
        pass "SSH key is available within CLI container."
      fi
    fi
  fi

  if [ "${VORTEX_DOCTOR_CHECK_WEBSERVER}" = "1" ]; then
    local_dev_url="$(docker compose exec -T cli bash -c 'echo ${VORTEX_LOCALDEV_URL}')"
    if [ -n "${local_dev_url}" ]; then
      # Depending on the type of installation, the homepage may return 200 or 403.
      if ! curl -L -s -o /dev/null -w "%{http_code}" "${local_dev_url}" | grep -q '200\|403'; then
        fail "Web server is not accessible at http://${local_dev_url}."
        exit 1
      fi
      pass "Web server is running and accessible at http://${local_dev_url}."

      if [ "${VORTEX_DOCTOR_CHECK_BOOTSTRAP}" = "1" ]; then
        if ! curl -L -s -N "${local_dev_url}" | grep -q -i "charset="; then
          fail "Website is running, but cannot be bootstrapped. Try pulling latest container images with 'ahoy pull'."
          exit 1
        fi
        pass "Bootstrapped website at http://${local_dev_url}."
      fi
    fi
  fi

  pass "All required checks have passed."
  echo
}

system_info() {
  echo "System information report"
  echo

  echo "OPERATING SYSTEM"
  if [ "$(uname)" = "Darwin" ]; then
    sw_vers
  else
    lsb_release -a
  fi
  echo

  echo "DOCKER"
  echo "Path to binary: $(which docker)"
  docker -v
  docker info
  echo

  echo "DOCKER COMPOSE V2"
  docker compose version || true
  echo

  echo "DOCKER-COMPOSE V1"
  echo "Path to binary: $(which docker-compose)"
  docker-compose version || true
  echo

  echo "PYGMY"
  echo "Path to binary: $(which pygmy)"
  pygmy version
  echo

  echo "AHOY"
  echo "Path to binary: $(which ahoy)"
  ahoy --version
  echo
}

#
# Check that command exists.
#
command_exists() {
  local cmd=${1}
  command -v "${cmd}" | grep -ohq "${cmd}"
  local res=$?

  # Try homebrew lookup, if brew is available.
  if command -v "brew" | grep -ohq "brew" && [ "${res}" = "1" ]; then
    brew --prefix "${cmd}" >/dev/null
    res=$?
  fi

  echo ${res}
}

main "$@"
