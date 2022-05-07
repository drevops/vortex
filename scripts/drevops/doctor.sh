#!/usr/bin/env bash
#
# Check DrevOps project requirements.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Check minimal Doctor requirements.
DREVOPS_DOCTOR_CHECK_MINIMAL="${DREVOPS_DOCTOR_CHECK_MINIMAL:-0}"

# Check pre-flight Doctor requirements.
DREVOPS_DOCTOR_CHECK_PREFLIGHT="${DREVOPS_DOCTOR_CHECK_PREFLIGHT:-0}"

if [ "${DREVOPS_DOCTOR_CHECK_MINIMAL}" = "1" ]; then
  DREVOPS_DOCTOR_CHECK_PORT=0
  DREVOPS_DOCTOR_CHECK_PYGMY=0
  DREVOPS_DOCTOR_CHECK_SSH=0
  DREVOPS_DOCTOR_CHECK_WEBSERVER=0
  DREVOPS_DOCTOR_CHECK_BOOTSTRAP=0
fi

if [ "${DREVOPS_DOCTOR_CHECK_PREFLIGHT}" = "1" ]; then
  DREVOPS_DOCTOR_CHECK_TOOLS="${DREVOPS_DOCTOR_CHECK_TOOLS:-1}"
  DREVOPS_DOCTOR_CHECK_PORT="${DREVOPS_DOCTOR_CHECK_PORT:-1}"
  DREVOPS_DOCTOR_CHECK_PYGMY="${DREVOPS_DOCTOR_CHECK_PYGMY:-1}"
  DREVOPS_DOCTOR_CHECK_CONTAINERS="${DREVOPS_DOCTOR_CHECK_CONTAINERS:-0}"
  DREVOPS_DOCTOR_CHECK_SSH="${DREVOPS_DOCTOR_CHECK_SSH:-0}"
  DREVOPS_DOCTOR_CHECK_WEBSERVER="${DREVOPS_DOCTOR_CHECK_WEBSERVER:-0}"
  DREVOPS_DOCTOR_CHECK_BOOTSTRAP="${DREVOPS_DOCTOR_CHECK_BOOTSTRAP:-0}"
fi

# Check Doctor requirements for presence of tools.
DREVOPS_DOCTOR_CHECK_TOOLS="${DREVOPS_DOCTOR_CHECK_TOOLS:-1}"

# Check Doctor requirements for port of availability.
DREVOPS_DOCTOR_CHECK_PORT="${DREVOPS_DOCTOR_CHECK_PORT:-1}"

# Check Doctor requirements for Pygmy of availability.
DREVOPS_DOCTOR_CHECK_PYGMY="${DREVOPS_DOCTOR_CHECK_PYGMY:-1}"

# Check Doctor requirements for container status.
DREVOPS_DOCTOR_CHECK_CONTAINERS="${DREVOPS_DOCTOR_CHECK_CONTAINERS:-1}"

# Check Doctor requirements for SSH key.
DREVOPS_DOCTOR_CHECK_SSH="${DREVOPS_DOCTOR_CHECK_SSH:-1}"

# Check Doctor requirements for webserver status.
DREVOPS_DOCTOR_CHECK_WEBSERVER="${DREVOPS_DOCTOR_CHECK_WEBSERVER:-1}"

# Check Doctor requirements for application bootstrap status.
DREVOPS_DOCTOR_CHECK_BOOTSTRAP="${DREVOPS_DOCTOR_CHECK_BOOTSTRAP:-1}"

# Local development URL.
DREVOPS_DOCTOR_LOCALDEV_URL="${DREVOPS_DOCTOR_LOCALDEV_URL:-${DREVOPS_LOCALDEV_URL}}"

# Default SSH key file.
DREVOPS_DOCTOR_SSH_KEY_FILE="${DREVOPS_DOCTOR_SSH_KEY_FILE:-${HOME}/.ssh/id_rsa}"

#-------------------------------------------------------------------------------

#
# Main entry point.
#
main() {
  [ "$1" = "info" ] && system_info && exit

  cecho blue "🔎 Checking project requirements"

  if [ "${DREVOPS_DOCTOR_CHECK_TOOLS}" = "1" ]; then
    [ "$(command_exists docker)" = "1" ] && error "Please install Docker (https://www.docker.com/get-started)." && exit 1
    [ "$(command_exists docker-compose)" = "1" ] && error "Please install docker-compose (https://docs.docker.com/compose/install/)." && exit 1
    [ "$(command_exists pygmy)" = "1" ] && error "Please install Pygmy (https://pygmy.readthedocs.io/)." && exit 1
    [ "$(command_exists ahoy)" = "1" ] && error "Please install Ahoy (https://ahoy-cli.readthedocs.io/)." && exit 1
    success "All required tools are present."
  fi

  if [ "${DREVOPS_DOCTOR_CHECK_PORT}" = "1" ] && [ "${OSTYPE}" != "linux-gnu" ]; then
    if ! lsof -i :80 | grep LISTEN | grep -q om.docke; then
      error "Port 80 is occupied by a service other than Docker. Stop this service and run 'pygmy up'."
    fi
    success "Port 80 is available."
  fi

  if [ "${DREVOPS_DOCTOR_CHECK_PYGMY}" = "1" ]; then
    if ! pygmy status > /dev/null 2>&1; then
      error "pygmy is not running. Run 'pygmy up' to start pygmy."
      exit 1
    fi
    success "Pygmy is running."
  fi

  # Check that the stack is running.
  if [ "${DREVOPS_DOCTOR_CHECK_CONTAINERS}" = "1" ]; then
    docker_services=(cli php nginx mariadb)
    for docker_service in "${docker_services[@]}"; do
    # shellcheck disable=SC2143
      if [ -z "$(docker-compose ps -q "${docker_service}")" ] || [ -z "$(docker ps -q --no-trunc | grep "$(docker-compose ps -q "${docker_service}")")" ]; then
        error "${docker_service} container is not running."
        error "$(docker-compose logs)"
        error "Run 'ahoy up'."
        exit 1
      fi
    done
    success "All containers are running"
  fi

  if [ "${DREVOPS_DOCTOR_CHECK_SSH}" = "1" ]; then
    # SSH key injection is required to access Lagoon services from within
    # containers. For example, to connect to production environment to run
    # drush script.
    # Pygmy makes this possible in the following way:
    # 1. Pygmy starts `amazeeio/ssh-agent` container with a volume `/tmp/amazeeio_ssh-agent`
    # 2. Pygmy adds a default SSH key from the host into this volume.
    # 3. `docker-compose.yml` should have volume inclusion specified for CLI container:
    #    ```
    #    volumes_from:
    #      - container:amazeeio-ssh-agent
    #    ```
    # 4. When CLI container starts, the volume is mounted and an entrypoint script
    #    adds SHH key into agent.
    #    @see https://github.com/uselagoon/lagoon-images/blob/main/images/php-cli/10-ssh-agent.sh
    #
    #  Running `ssh-add -L` within CLI container should show that the SSH key
    #  is correctly loaded.
    #
    # As rule of a thumb, one must restart the CLI container after restarting
    # Pygmy ONLY if SSH key was not loaded in pygmy before the stack starts.
    # No need to restart CLI container if key was added, but pygmy was
    # restarted - the volume mount will retain and the key will still be
    # available in CLI container.

    ssh_key_added=1
    # Check that the key is injected into pygmy ssh-agent container.
    if ! pygmy status 2>&1 | grep -q "${DREVOPS_DOCTOR_SSH_KEY_FILE}"; then
      warning "SSH key is not added to pygmy. Run 'pygmy restart' and then 'ahoy up -- --build'."
      ssh_key_added=0
    fi

    # Check that the volume is mounted into CLI container.
    if ! docker exec -i "$(docker-compose ps -q cli)" bash -c "grep \"^/dev\" /etc/mtab | grep -q /tmp/amazeeio_ssh-agent"; then
      warning "SSH key is added to Pygmy, but the volume is not mounted into container. Make sure that your your \"docker-compose.yml\" has the following lines:"
      warning "volumes_from:"
      warning "  - container:amazeeio-ssh-agent"
      warning "After adding these lines, run 'ahoy up -- --build'."
      ssh_key_added=0
    fi

    # Check that ssh key is available in the container.
    if [ "${ssh_key_added}" = "1" ] && ! docker exec -i "$(docker-compose ps -q cli)" bash -c "ssh-add -L | grep -q 'ssh-rsa'" ; then
      warning "SSH key was not added into container. Run 'pygmy restart'."
      ssh_key_added=0
    fi

    [ "${ssh_key_added}" = "1" ] && success "SSH key is available within CLI container."
  fi

  if [ -n "${DREVOPS_DOCTOR_LOCALDEV_URL}" ]; then
    if [ "${DREVOPS_DOCTOR_CHECK_WEBSERVER}" = "1" ]; then
      # Depending on the type of installation, the homepage may return 200 or 403.
      if ! curl -L -s -o /dev/null -w "%{http_code}" "${DREVOPS_DOCTOR_LOCALDEV_URL}" | grep -q '200\|403'; then
        error "Web server is not accessible at http://${DREVOPS_DOCTOR_LOCALDEV_URL}."
        exit 1
      fi
      success "Web server is running and accessible at http://${DREVOPS_DOCTOR_LOCALDEV_URL}."
    fi

    if [ "${DREVOPS_DOCTOR_CHECK_BOOTSTRAP}" = "1" ]; then
      if ! curl -L -s -N "${DREVOPS_DOCTOR_LOCALDEV_URL}" | grep -q -i "charset="; then
        error "Website is running, but cannot be bootstrapped. Try pulling latest container images with 'ahoy pull'."
        exit 1
      fi
      success "Successfully bootstrapped website at http://${DREVOPS_DOCTOR_LOCALDEV_URL}."
    fi
  fi

  cecho blue "👌 All required checks have passed."
}

system_info() {
  status "System information report"
  echo

  heading "- Operating system -"
  if [ "$(uname)" = "Darwin" ]; then
    sw_vers
  else
    lsb_release -a
  fi
  echo

  heading "- Docker -"
  echo "Path to binary: $(which docker)"
  docker -v
  docker info
  echo

  heading "- Docker Compose -"
  echo "Path to binary: $(which docker-compose)"
  docker-compose version
  echo

  heading "- Pygmy -"
  echo "Path to binary: $(which pygmy)"
  pygmy version
  echo

  heading "- Ahoy -"
  echo "Path to binary: $(which ahoy)"
  ahoy --version
  echo
}

#
# Check that command exists.
#
command_exists() {
  local cmd=$1
  command -v "${cmd}" | grep -ohq "${cmd}"
  local res=$?

  # Try homebrew lookup, if brew is available.
  if command -v "brew" | grep -ohq "brew" && [ "$res" = "1" ] ; then
    brew --prefix "${cmd}" > /dev/null
    res=$?
  fi

  echo ${res}
}

#
# Status echo.
#
status() {
  cecho blue "✚ $1";
}

#
# Warning echo.
#
warning() {
  cecho yellow "  ⚠  $1";
}

#
# Success echo.
#
success() {
  cecho green "  ✓ $1";
}

#
# Error echo.
#
error() {
  cecho red "  ✘ $1";
  exit 1
}

#
# Heading echo.
#
heading() {
  cecho yellow "$1";
}

#
# Colored echo.
#
cecho() {
  local prefix="\033["
  local input_color=$1
  local message="$2"

  local color=""
  case "$input_color" in
    black  | bk) color="${prefix}0;30m";;
    red    |  r) color="${prefix}1;31m";;
    green  |  g) color="${prefix}1;32m";;
    yellow |  y) color="${prefix}1;33m";;
    blue   |  b) color="${prefix}1;34m";;
    purple |  p) color="${prefix}1;35m";;
    cyan   |  c) color="${prefix}1;36m";;
    gray   | gr) color="${prefix}0;37m";;
    *) message="$1"
  esac

  # Format message with color codes, but only if an output supports colors and
  # a correct color was provided.
  if [ -t 1 ]; then
    [ -n "$color" ] && message="${color}${message}${prefix}0m"
  fi

  echo -e "$message"
}

main "$@"
