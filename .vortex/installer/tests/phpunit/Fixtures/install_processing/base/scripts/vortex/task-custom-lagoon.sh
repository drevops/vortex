#!/usr/bin/env bash
##
# Run custom Lagoon task.
#
# @see https://github.com/amazeeio/lagoon-cli
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# The task name.
VORTEX_TASK_LAGOON_NAME="${VORTEX_TASK_LAGOON_NAME:-Automation task}"

# The Lagoon project to run tasks for.
VORTEX_TASK_LAGOON_PROJECT="${VORTEX_TASK_LAGOON_PROJECT:-${LAGOON_PROJECT:-}}"

# The Lagoon branch to run the task on.
VORTEX_TASK_LAGOON_BRANCH="${VORTEX_TASK_LAGOON_BRANCH:-}"

# The task command to execute.
VORTEX_TASK_LAGOON_COMMAND="${VORTEX_TASK_LAGOON_COMMAND:-}"

# The Lagoon instance name to interact with.
VORTEX_TASK_LAGOON_INSTANCE="${VORTEX_TASK_LAGOON_INSTANCE:-amazeeio}"

# The Lagoon instance GraphQL endpoint to interact with.
VORTEX_TASK_LAGOON_INSTANCE_GRAPHQL="${VORTEX_TASK_LAGOON_INSTANCE_GRAPHQL:-https://api.lagoon.amazeeio.cloud/graphql}"

# The Lagoon instance hostname to interact with.
VORTEX_TASK_LAGOON_INSTANCE_HOSTNAME="${VORTEX_TASK_LAGOON_INSTANCE_HOSTNAME:-ssh.lagoon.amazeeio.cloud}"

# The Lagoon instance port to interact with.
VORTEX_TASK_LAGOON_INSTANCE_PORT="${VORTEX_TASK_LAGOON_INSTANCE_PORT:-32222}"

# SSH key fingerprint used to connect to a remote.
VORTEX_TASK_SSH_FINGERPRINT="${VORTEX_TASK_SSH_FINGERPRINT:-}"

# Default SSH file used if custom fingerprint is not provided.
VORTEX_TASK_SSH_FILE="${VORTEX_TASK_SSH_FILE:-${HOME}/.ssh/id_rsa}"

# Location of the Lagoon CLI binary.
VORTEX_LAGOONCLI_PATH="${VORTEX_LAGOONCLI_PATH:-/tmp}"

# Flag to force the installation of Lagoon CLI.
VORTEX_LAGOONCLI_FORCE_INSTALL="${VORTEX_LAGOONCLI_FORCE_INSTALL:-}"

# Lagoon CLI version to use.
VORTEX_LAGOONCLI_VERSION="${VORTEX_LAGOONCLI_VERSION:-latest}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started Lagoon task ${VORTEX_TASK_LAGOON_NAME}."

## Check all required values.
[ -z "${VORTEX_TASK_LAGOON_NAME}" ] && echo "Missing required value for VORTEX_TASK_LAGOON_NAME." && exit 1
[ -z "${VORTEX_TASK_LAGOON_BRANCH}" ] && echo "Missing required value for VORTEX_TASK_LAGOON_BRANCH." && exit 1
[ -z "${VORTEX_TASK_LAGOON_COMMAND}" ] && echo "Missing required value for VORTEX_TASK_LAGOON_COMMAND." && exit 1
[ -z "${VORTEX_TASK_LAGOON_PROJECT}" ] && echo "Missing required value for VORTEX_TASK_LAGOON_PROJECT." && exit 1

export VORTEX_SSH_PREFIX="TASK" && . ./scripts/vortex/setup-ssh.sh

if ! command -v lagoon >/dev/null || [ -n "${VORTEX_LAGOONCLI_FORCE_INSTALL}" ]; then
  note "Installing Lagoon CLI."

  lagooncli_download_url="https://api.github.com/repos/uselagoon/lagoon-cli/releases/latest"
  if [ "${VORTEX_LAGOONCLI_VERSION}" != "latest" ]; then
    lagooncli_download_url="https://api.github.com/repos/uselagoon/lagoon-cli/releases/tags/${VORTEX_LAGOONCLI_VERSION}"
  fi

  curl -sL "${lagooncli_download_url}" |
    grep "browser_download_url" |
    grep -i "$(uname -s)-amd64\"$" |
    cut -d '"' -f 4 |
    xargs curl -L -o "${VORTEX_LAGOONCLI_PATH}/lagoon"
  chmod +x "${VORTEX_LAGOONCLI_PATH}/lagoon"
  export PATH="${PATH}:${VORTEX_LAGOONCLI_PATH}"
fi

for cmd in curl lagoon; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

note "Configuring Lagoon instance."
#shellcheck disable=SC2218
lagoon config add --force -l "${VORTEX_TASK_LAGOON_INSTANCE}" -g "${VORTEX_TASK_LAGOON_INSTANCE_GRAPHQL}" -H "${VORTEX_TASK_LAGOON_INSTANCE_HOSTNAME}" -P "${VORTEX_TASK_LAGOON_INSTANCE_PORT}"

lagoon() { command lagoon --force --skip-update-check -i "${VORTEX_TASK_SSH_FILE}" -l "${VORTEX_TASK_LAGOON_INSTANCE}" -p "${VORTEX_TASK_LAGOON_PROJECT}" "$@"; }

note "Creating ${VORTEX_TASK_LAGOON_NAME} task: project ${VORTEX_TASK_LAGOON_PROJECT}, branch: ${VORTEX_TASK_LAGOON_BRANCH}."
lagoon run custom -e "${VORTEX_TASK_LAGOON_BRANCH}" -N "${VORTEX_TASK_LAGOON_NAME}" -c "${VORTEX_TASK_LAGOON_COMMAND}"

pass "Finished Lagoon task ${VORTEX_TASK_LAGOON_NAME}."
