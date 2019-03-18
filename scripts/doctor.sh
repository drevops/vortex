#!/usr/bin/env bash
#
# Check Drupal-Dev project requirements.
#
set -e

DOCTOR_CHECK_TOOLS="${DOCTOR_CHECK_TOOLS:-1}"
DOCTOR_CHECK_PORT="${DOCTOR_CHECK_PORT:-1}"
DOCTOR_CHECK_SSH="${DOCTOR_CHECK_SSH:-1}"
DOCTOR_CHECK_DB="${DOCTOR_CHECK_DB:-1}"
DOCTOR_CHECK_BOOTSTRAP="${DOCTOR_CHECK_BOOTSTRAP:-1}"

LOCALDEV_URL=${LOCALDEV_URL:-http://your-site.docker.amazee.io/}

DATAROOT="${DATAROOT:-.data}"

#-------------------------------------------------------------------------------
#                    DO NOT CHANGE ANYTHING BELOW THIS LINE
#-------------------------------------------------------------------------------


#
# Main entry point.
#
main() {
  # Check project requirements.
  status "Checking project requirements"

  if [ "${DOCTOR_CHECK_TOOLS}" == "1" ]; then
    [ "$(command_exists docker)" == "1" ] && error "Please install Docker." && exit 1
    [ "$(command_exists docker-compose)" == "1" ] && error "Please install docker-compose." && exit 1
    [ "$(command_exists composer)" == "1" ] && error "Please install composer: visit https://getcomposer.org/" && exit 1
    [ "$(command_exists pygmy)" == "1" ] && error "Please install pygmy" && exit 1
    [ "$(command_exists ahoy)" == "1" ] && error "Please install Ahoy." && exit 1
  fi

  if [ "${DOCTOR_CHECK_DB}" == "1" ]; then
      if [ ! -e "${DATAROOT}/db.sql" ]; then
        error "Unable to find database dump file \"${DATAROOT}/db.sql\". Please place db.sql file into \"${DATAROOT}\" or configure one of the integrations and use \"ahoy download-db\".";
      fi
  fi

  if [ "${DOCTOR_CHECK_PORT}" == "1" ]; then
    # Check what is listening on port 80.
    if ! lsof -i :80 | grep -q LISTEN; then
      error "Nothing is listening on port 80. Run 'pygmy up' to start pygmy." && exit 1
    elif ! lsof -i :80 | grep LISTEN | grep -q om.docke; then
      error "Port 80 is occupied by other service. Stop this service and run 'pygmy up'"
    else
      pygmy_status=$(pygmy status)
      [ "${pygmy_status}" == "1" ] && error "pygmy is not running. Run 'pygmy up' to start pygmy." && exit 1
      # @todo: Add more checks for pygmy's services.
    fi
  fi

  if [ "${DOCTOR_CHECK_SSH}" == "1" ]; then
    docker exec -i "$(docker-compose ps -q cli)" bash -c "ssh-add -L | grep -vq 'ssh-rsa'" && error "SSH key was not added into container. Run 'pygmy restart'." && exit 1
  fi

  if [ "${DOCTOR_CHECK_BOOTSTRAP}" == "1" ]; then
    curl -L -s -o /dev/null -w "%{http_code}" "${LOCALDEV_URL}" | grep -q -v 200 && error "Unable to access ${LOCALDEV_URL}" && exit 1

    if curl -L -s -N "${LOCALDEV_URL}" | grep -q "name=\"Generator\" content=\"Drupal 8"; then
      success "Successfully bootstrapped ${LOCALDEV_URL}"
    else
      error "Website is running, but cannot be bootstrapped. Try pulling latest container images with 'composer bay:pull'" && exit 1
    fi
  fi
}

#
# Check that command exists.
#
command_exists() {
  local cmd=$1
  command -v "${cmd}" | grep -ohq "${cmd}"
  local res=$?

  # Try homebrew lookup, if brew is available.
  if command -v "brew" | grep -ohq "brew" && [ "$res" == "1" ] ; then
    brew --prefix "${cmd}" > /dev/null
    res=$?
  fi

  echo ${res}
}

#
# Status echo.
#
status() {
  cecho blue "==> $1";
}

#
# Success echo.
#
success() {
  cecho green "✓ $1";
}

#
# Error echo.
#
error() {
  cecho red "✘ $1";
  exit 1
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

  # Format message with color codes, but only if a correct color was provided.
  [ -n "$color" ] && message="${color}${message}${prefix}0m"

  echo -e "$message"
}

main "$@"
