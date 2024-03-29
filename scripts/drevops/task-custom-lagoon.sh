#!/usr/bin/env bash
##
# Run custom Lagoon task.
#
# @see https://github.com/amazeeio/lagoon-cli
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# The task name.
DREVOPS_TASK_LAGOON_NAME="${DREVOPS_TASK_LAGOON_NAME:-Automation task}"

# The Lagoon project to run tasks for.
DREVOPS_TASK_LAGOON_PROJECT="${DREVOPS_TASK_LAGOON_PROJECT:-${LAGOON_PROJECT:-}}"

# The Lagoon branch to run the task on.
DREVOPS_TASK_LAGOON_BRANCH="${DREVOPS_TASK_LAGOON_BRANCH:-}"

# The task command to execute.
DREVOPS_TASK_LAGOON_COMMAND="${DREVOPS_TASK_LAGOON_COMMAND:-}"

# The Lagoon instance name to interact with.
DREVOPS_TASK_LAGOON_INSTANCE="${DREVOPS_TASK_LAGOON_INSTANCE:-amazeeio}"

# The Lagoon instance GraphQL endpoint to interact with.
DREVOPS_TASK_LAGOON_INSTANCE_GRAPHQL="${DREVOPS_TASK_LAGOON_INSTANCE_GRAPHQL:-https://api.lagoon.amazeeio.cloud/graphql}"

# The Lagoon instance hostname to interact with.
DREVOPS_TASK_LAGOON_INSTANCE_HOSTNAME="${DREVOPS_TASK_LAGOON_INSTANCE_HOSTNAME:-ssh.lagoon.amazeeio.cloud}"

# The Lagoon instance port to interact with.
DREVOPS_TASK_LAGOON_INSTANCE_PORT="${DREVOPS_TASK_LAGOON_INSTANCE_PORT:-32222}"

# SSH key fingerprint used to connect to a remote.
DREVOPS_TASK_SSH_FINGERPRINT="${DREVOPS_TASK_SSH_FINGERPRINT:-}"

# Default SSH file used if custom fingerprint is not provided.
DREVOPS_TASK_SSH_FILE="${DREVOPS_TASK_SSH_FILE:-${HOME}/.ssh/id_rsa}"

# Location of the Lagoon CLI binary.
DREVOPS_TASK_LAGOON_BIN_PATH="${DREVOPS_TASK_LAGOON_BIN_PATH:-/tmp}"

# Flag to force the installation of Lagoon CLI.
DREVOPS_TASK_LAGOON_INSTALL_CLI_FORCE="${DREVOPS_TASK_LAGOON_INSTALL_CLI_FORCE:-}"

# Lagoon CLI version to use.
DREVOPS_TASK_LAGOON_LAGOONCLI_VERSION="${DREVOPS_TASK_LAGOON_LAGOONCLI_VERSION:-latest}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started Lagoon task ${DREVOPS_TASK_LAGOON_NAME}."

## Check all required values.
[ -z "${DREVOPS_TASK_LAGOON_NAME}" ] && echo "Missing required value for DREVOPS_TASK_LAGOON_NAME." && exit 1
[ -z "${DREVOPS_TASK_LAGOON_BRANCH}" ] && echo "Missing required value for DREVOPS_TASK_LAGOON_BRANCH." && exit 1
[ -z "${DREVOPS_TASK_LAGOON_COMMAND}" ] && echo "Missing required value for DREVOPS_TASK_LAGOON_COMMAND." && exit 1
[ -z "${DREVOPS_TASK_LAGOON_PROJECT}" ] && echo "Missing required value for DREVOPS_TASK_LAGOON_PROJECT." && exit 1

DREVOPS_SSH_PREFIX="TASK" ./scripts/drevops/setup-ssh.sh

if ! command -v lagoon >/dev/null || [ -n "${DREVOPS_TASK_LAGOON_INSTALL_CLI_FORCE}" ]; then
  note "Installing Lagoon CLI."

  lagooncli_download_url="https://api.github.com/repos/uselagoon/lagoon-cli/releases/latest"
  if [ "${DREVOPS_TASK_LAGOON_LAGOONCLI_VERSION}" != "latest" ]; then
    lagooncli_download_url="https://api.github.com/repos/uselagoon/lagoon-cli/releases/tags/${DREVOPS_TASK_LAGOON_LAGOONCLI_VERSION}"
  fi

  curl -sL "${lagooncli_download_url}" |
    grep "browser_download_url" |
    grep -i "$(uname -s)-amd64\"$" |
    cut -d '"' -f 4 |
    xargs curl -L -o "${DREVOPS_TASK_LAGOON_BIN_PATH}/lagoon"
  chmod +x "${DREVOPS_TASK_LAGOON_BIN_PATH}/lagoon"
  export PATH="${PATH}:${DREVOPS_TASK_LAGOON_BIN_PATH}"
fi

note "Configuring Lagoon instance."
#shellcheck disable=SC2218
lagoon config add --force -l "${DREVOPS_TASK_LAGOON_INSTANCE}" -g "${DREVOPS_TASK_LAGOON_INSTANCE_GRAPHQL}" -H "${DREVOPS_TASK_LAGOON_INSTANCE_HOSTNAME}" -P "${DREVOPS_TASK_LAGOON_INSTANCE_PORT}"
lagoon() { command lagoon --force --skip-update-check -i "${DREVOPS_TASK_SSH_FILE}" -l "${DREVOPS_TASK_LAGOON_INSTANCE}" -p "${DREVOPS_TASK_LAGOON_PROJECT}" "$@"; }

note "Creating ${DREVOPS_TASK_LAGOON_NAME} task: project ${DREVOPS_TASK_LAGOON_PROJECT}, branch: ${DREVOPS_TASK_LAGOON_BRANCH}."
lagoon run custom -e "${DREVOPS_TASK_LAGOON_BRANCH}" -N "${DREVOPS_TASK_LAGOON_NAME}" -c "${DREVOPS_TASK_LAGOON_COMMAND}"

pass "Finished Lagoon task ${DREVOPS_TASK_LAGOON_NAME}."
