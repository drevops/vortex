#!/usr/bin/env bash
##
# Run custom Lagoon task.
#
# @see https://github.com/uselagoon/lagoon-cli
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# The task name.
VORTEX_TASK_CUSTOM_LAGOON_NAME="${VORTEX_TASK_CUSTOM_LAGOON_NAME:-Automation task}"

# The Lagoon project to run tasks for.
VORTEX_TASK_CUSTOM_LAGOON_PROJECT="${VORTEX_TASK_CUSTOM_LAGOON_PROJECT:-${LAGOON_PROJECT:-}}"

# The Lagoon branch to run the task on.
VORTEX_TASK_CUSTOM_LAGOON_BRANCH="${VORTEX_TASK_CUSTOM_LAGOON_BRANCH:-}"

# The task command to execute.
VORTEX_TASK_CUSTOM_LAGOON_COMMAND="${VORTEX_TASK_CUSTOM_LAGOON_COMMAND:-}"

# The Lagoon instance name to interact with.
VORTEX_TASK_CUSTOM_LAGOON_INSTANCE="${VORTEX_TASK_CUSTOM_LAGOON_INSTANCE:-amazeeio}"

# The Lagoon instance GraphQL endpoint to interact with.
VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_GRAPHQL="${VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_GRAPHQL:-https://api.lagoon.amazeeio.cloud/graphql}"

# The Lagoon instance hostname to interact with.
VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_HOSTNAME="${VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_HOSTNAME:-ssh.lagoon.amazeeio.cloud}"

# The Lagoon instance port to interact with.
VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_PORT="${VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_PORT:-32222}"

# SSH key fingerprint used to connect to a remote.
VORTEX_TASK_CUSTOM_LAGOON_SSH_FINGERPRINT="${VORTEX_TASK_CUSTOM_LAGOON_SSH_FINGERPRINT:-}"

# Default SSH file used if custom fingerprint is not provided.
VORTEX_TASK_CUSTOM_LAGOON_SSH_FILE="${VORTEX_TASK_CUSTOM_LAGOON_SSH_FILE:-${HOME}/.ssh/id_rsa}"

# Location of the Lagoon CLI binary.
VORTEX_TASK_CUSTOM_LAGOON_CLI_PATH="${VORTEX_TASK_CUSTOM_LAGOON_CLI_PATH:-/tmp}"

# Flag to force the installation of Lagoon CLI.
VORTEX_TASK_CUSTOM_LAGOON_CLI_FORCE_INSTALL="${VORTEX_TASK_CUSTOM_LAGOON_CLI_FORCE_INSTALL:-}"

# Lagoon CLI version to use.
VORTEX_TASK_CUSTOM_LAGOON_CLI_VERSION="${VORTEX_TASK_CUSTOM_LAGOON_CLI_VERSION:-v0.32.0}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started Lagoon task ${VORTEX_TASK_CUSTOM_LAGOON_NAME}."

## Check all required values.
[ -z "${VORTEX_TASK_CUSTOM_LAGOON_NAME}" ] && fail "Missing required value for VORTEX_TASK_CUSTOM_LAGOON_NAME or VORTEX_TASK_LAGOON_NAME." && exit 1
[ -z "${VORTEX_TASK_CUSTOM_LAGOON_BRANCH}" ] && fail "Missing required value for VORTEX_TASK_CUSTOM_LAGOON_BRANCH or VORTEX_TASK_LAGOON_BRANCH." && exit 1
[ -z "${VORTEX_TASK_CUSTOM_LAGOON_COMMAND}" ] && fail "Missing required value for VORTEX_TASK_CUSTOM_LAGOON_COMMAND or VORTEX_TASK_LAGOON_COMMAND." && exit 1
[ -z "${VORTEX_TASK_CUSTOM_LAGOON_PROJECT}" ] && fail "Missing required value for VORTEX_TASK_CUSTOM_LAGOON_PROJECT or VORTEX_TASK_LAGOON_PROJECT." && exit 1

export VORTEX_SSH_PREFIX="TASK_CUSTOM_LAGOON" && . ./scripts/vortex/setup-ssh.sh

if ! command -v lagoon >/dev/null || [ -n "${VORTEX_TASK_CUSTOM_LAGOON_CLI_FORCE_INSTALL}" ]; then
  task "Installing Lagoon CLI."

  platform=$(uname -s | tr '[:upper:]' '[:lower:]')
  arch_suffix=$(uname -m | sed 's/x86_64/amd64/;s/aarch64/arm64/')
  download_url="https://github.com/uselagoon/lagoon-cli/releases/download/${VORTEX_TASK_CUSTOM_LAGOON_CLI_VERSION}/lagoon-cli-${VORTEX_TASK_CUSTOM_LAGOON_CLI_VERSION}-${platform}-${arch_suffix}"

  note "Downloading Lagoon CLI from ${download_url}."
  curl -fSLs -o "${VORTEX_TASK_CUSTOM_LAGOON_CLI_PATH}/lagoon" "${download_url}"

  note "Installing Lagoon CLI to ${VORTEX_TASK_CUSTOM_LAGOON_CLI_PATH}/lagoon."
  chmod +x "${VORTEX_TASK_CUSTOM_LAGOON_CLI_PATH}/lagoon"
  export PATH="${PATH}:${VORTEX_TASK_CUSTOM_LAGOON_CLI_PATH}"
fi

for cmd in curl lagoon; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

task "Configuring Lagoon instance."
#shellcheck disable=SC2218
lagoon config add --force -l "${VORTEX_TASK_CUSTOM_LAGOON_INSTANCE}" -g "${VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_GRAPHQL}" -H "${VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_HOSTNAME}" -P "${VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_PORT}"

lagoon() { command lagoon --force --skip-update-check -i "${VORTEX_TASK_CUSTOM_LAGOON_SSH_FILE}" -l "${VORTEX_TASK_CUSTOM_LAGOON_INSTANCE}" -p "${VORTEX_TASK_CUSTOM_LAGOON_PROJECT}" "$@"; }

task "Creating ${VORTEX_TASK_CUSTOM_LAGOON_NAME} task: project ${VORTEX_TASK_CUSTOM_LAGOON_PROJECT}, branch: ${VORTEX_TASK_CUSTOM_LAGOON_BRANCH}."
lagoon run custom -e "${VORTEX_TASK_CUSTOM_LAGOON_BRANCH}" -N "${VORTEX_TASK_CUSTOM_LAGOON_NAME}" -c "${VORTEX_TASK_CUSTOM_LAGOON_COMMAND}"

pass "Finished Lagoon task ${VORTEX_TASK_CUSTOM_LAGOON_NAME}."
