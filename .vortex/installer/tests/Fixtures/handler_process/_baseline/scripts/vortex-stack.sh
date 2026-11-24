#!/usr/bin/env bash
##
# Start the stack, recovering containers left on removed networks or volumes.
#
# Usage: ./scripts/vortex-stack.sh up|start [arguments]
#
# 'up' passes the arguments to 'docker compose up --detach'. 'start' passes
# them to 'docker compose start', or to 'docker compose up --detach' when the
# project has no containers yet.
#
# Every external network and every 'volumes_from' container declared in the
# Docker Compose configuration must exist before the stack starts. Docker
# attaches a container to a network by ID, so a container whose network was
# removed fails to start until it is recreated. A container that lacks the
# current volumes of its 'volumes_from' container, for example after Pygmy
# replaced its SSH agent, is recreated as well.
#

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

# @formatter:off
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { _TASK_START=$(date +%s); [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
pass() { _d=""; [ -n "${_TASK_START:-}" ] && _d=" ($(($(date +%s) - _TASK_START))s)" && unset _TASK_START; [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s%s\033[0m\n" "${1}" "${_d}" || printf "[ OK ] %s%s\n" "${1}" "${_d}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; exit "${2:-1}"; }
# @formatter:on

subcommand="${1:-}"
[ "${subcommand}" != "up" ] && [ "${subcommand}" != "start" ] && fail "Unknown subcommand '${subcommand}'. Use 'up' or 'start'."
shift

# An invalid configuration skips the checks below, so that Docker Compose
# reports the error once, from the command that starts the stack.
config="$(docker compose config 2>/dev/null)" || config=""

# Print 'network <name>' for each external network and
# 'volumes_from <service> <container>' for each 'volumes_from' container.
dependencies="$(echo "${config}" | awk '
  function flush() {
    if (network_external) print "network", network_name
    network_name = ""
    network_external = 0
  }
  /^[^ ]/ { flush(); section = $1; next }
  section == "services:" && /^  [^ ]/ { service = $1; sub(/:$/, "", service); in_volumes_from = 0; next }
  section == "services:" && /^    volumes_from:/ { in_volumes_from = 1; next }
  section == "services:" && in_volumes_from && /^      - container:/ { container = $2; sub(/^container:/, "", container); print "volumes_from", service, container; next }
  section == "services:" && /^    [^ ]/ { in_volumes_from = 0; next }
  section == "networks:" && /^  [^ ]/ { flush(); network_name = $1; sub(/:$/, "", network_name); next }
  section == "networks:" && /^    name: / { network_name = $2; next }
  section == "networks:" && /^    external: true/ { network_external = 1; next }
  END { flush() }
')"

has_missing=0
while read -r type name container; do
  [ -z "${type}" ] && continue

  if [ "${type}" = "network" ]; then
    docker network inspect "${name}" >/dev/null 2>&1 || {
      note "Network '${name}' is declared as external but does not exist."
      has_missing=1
    }
    continue
  fi

  running="$(docker inspect --format '{{.State.Running}}' "${container}" 2>/dev/null)" || running=""
  if [ -z "${running}" ]; then
    note "Container '${container}' that provides volumes to service '${name}' does not exist."
    has_missing=1
  elif [ "${running}" != "true" ]; then
    note "Container '${container}' that provides volumes to service '${name}' is not running, so these volumes may be incomplete."
  fi
done <<<"${dependencies}"

[ "${has_missing}" = "1" ] && fail "Required Docker resources are missing. Start the tool that provides them, such as Pygmy with 'pygmy up', and run this command again."

containers="$(docker compose ps --all --format '{{.Service}}:{{.ID}}' 2>/dev/null)" || containers=""

if [ "${subcommand}" = "start" ] && [ -z "${containers}" ]; then
  note "No containers to start. Creating them instead."
  subcommand="up"
fi

is_force_recreate=0
for arg in "$@"; do
  [ "${arg}" = "--force-recreate" ] && is_force_recreate=1
done

stale_services=()
if [ "${is_force_recreate}" = "0" ]; then
  for entry in ${containers}; do
    service="${entry%%:*}"
    id="${entry#*:}"

    # Skip services already marked by another container of the same service.
    [[ " ${stale_services[*]-} " == *" ${service} "* ]] && continue

    details="$(docker inspect --format '{{range .NetworkSettings.Networks}}{{.NetworkID}} {{end}}|{{range .Mounts}}{{.Name}} {{end}}' "${id}")"
    network_ids="${details%%|*}"
    volumes=" ${details#*|} "

    for network_id in ${network_ids}; do
      if ! docker network inspect "${network_id}" >/dev/null 2>&1; then
        note "Service '${service}' is attached to a network that no longer exists."
        stale_services+=("${service}")
        break
      fi
    done

    [[ " ${stale_services[*]-} " == *" ${service} "* ]] && continue

    while read -r type name container; do
      if [ "${type}" != "volumes_from" ] || [ "${name}" != "${service}" ]; then
        continue
      fi

      for volume in $(docker inspect --format '{{range .Mounts}}{{.Name}} {{end}}' "${container}"); do
        if [[ ${volumes} != *" ${volume} "* ]]; then
          note "Service '${service}' is not using the current volumes of container '${container}'."
          stale_services+=("${service}")
          break 2
        fi
      done
    done <<<"${dependencies}"
  done
fi

# Recreated containers are left stopped, so the command below starts them in
# dependency order.
if [ "${#stale_services[@]}" -gt 0 ]; then
  task "Recreating containers for services: ${stale_services[*]}."
  docker compose up --no-start --no-deps --force-recreate "${stale_services[@]}"
  pass "Recreated containers for services: ${stale_services[*]}."
fi

if [ "${subcommand}" = "start" ]; then
  docker compose start "$@"
else
  docker compose up --detach "$@"
fi
