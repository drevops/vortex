#!/usr/bin/env bash
##
# Notify about events.
#
# This is a router script to call relevant scripts based on type.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Notification channels.
#
# Can be a combination of comma-separated values: email,slack,newrelic,github,jira,webhook
VORTEX_NOTIFY_CHANNELS="${VORTEX_NOTIFY_CHANNELS:-email}"

# Notification event type.
#
# Can be 'pre_deployment' or 'post_deployment'.
VORTEX_NOTIFY_EVENT="${VORTEX_NOTIFY_EVENT:-post_deployment}"

# Notification skip flag.
VORTEX_NOTIFY_SKIP="${VORTEX_NOTIFY_SKIP:-}"

# Notification project name.
VORTEX_NOTIFY_PROJECT="${VORTEX_NOTIFY_PROJECT:-${VORTEX_PROJECT:-}}"

# Notification git branch name.
VORTEX_NOTIFY_BRANCH="${VORTEX_NOTIFY_BRANCH:-}"

# Notification git commit SHA.
VORTEX_NOTIFY_SHA="${VORTEX_NOTIFY_SHA:-}"

# Notification pull request number.
VORTEX_NOTIFY_PR_NUMBER="${VORTEX_NOTIFY_PR_NUMBER:-}"

# Notification deployment label (human-readable identifier for display).
VORTEX_NOTIFY_LABEL="${VORTEX_NOTIFY_LABEL:-}"

# Notification environment URL (where the site was deployed).
VORTEX_NOTIFY_ENVIRONMENT_URL="${VORTEX_NOTIFY_ENVIRONMENT_URL:-}"

# Notification login URL (defaults to ENVIRONMENT_URL/user/login if not provided).
VORTEX_NOTIFY_LOGIN_URL="${VORTEX_NOTIFY_LOGIN_URL:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started dispatching notifications."

[ -n "${VORTEX_NOTIFY_SKIP:-}" ] && pass "Skipping dispatching notifications." && exit 0

# Validate required variables.
[ -z "${VORTEX_NOTIFY_BRANCH}" ] && fail "Missing required value for VORTEX_NOTIFY_BRANCH" && exit 1
[ -z "${VORTEX_NOTIFY_SHA}" ] && fail "Missing required value for VORTEX_NOTIFY_SHA" && exit 1
[ -z "${VORTEX_NOTIFY_LABEL}" ] && fail "Missing required value for VORTEX_NOTIFY_LABEL" && exit 1
[ -z "${VORTEX_NOTIFY_ENVIRONMENT_URL}" ] && fail "Missing required value for VORTEX_NOTIFY_ENVIRONMENT_URL" && exit 1

# Auto-generate LOGIN_URL if not provided.
if [ -z "${VORTEX_NOTIFY_LOGIN_URL}" ]; then
  VORTEX_NOTIFY_LOGIN_URL="${VORTEX_NOTIFY_ENVIRONMENT_URL}/user/login"
fi

# Export variables so notification scripts can use them.
export VORTEX_NOTIFY_BRANCH
export VORTEX_NOTIFY_SHA
export VORTEX_NOTIFY_PR_NUMBER
export VORTEX_NOTIFY_LABEL
export VORTEX_NOTIFY_ENVIRONMENT_URL
export VORTEX_NOTIFY_LOGIN_URL

# Validate event type (scripts will handle event-specific logic).
if [ "${VORTEX_NOTIFY_EVENT}" != "pre_deployment" ] && [ "${VORTEX_NOTIFY_EVENT}" != "post_deployment" ]; then
  fail "Unsupported event ${VORTEX_NOTIFY_EVENT} provided." && exit 1
fi

info "Notification summary:"
note "Project        : ${VORTEX_NOTIFY_PROJECT}"
note "Branch         : ${VORTEX_NOTIFY_BRANCH}"
note "SHA            : ${VORTEX_NOTIFY_SHA}"
note "PR Number      : ${VORTEX_NOTIFY_PR_NUMBER:-<none>}"
note "Label          : ${VORTEX_NOTIFY_LABEL}"
note "Environment URL: ${VORTEX_NOTIFY_ENVIRONMENT_URL}"
note "Login URL      : ${VORTEX_NOTIFY_LOGIN_URL}"
note "Event          : ${VORTEX_NOTIFY_EVENT}"
note "Channels       : ${VORTEX_NOTIFY_CHANNELS}"

if [ -z "${VORTEX_NOTIFY_CHANNELS##*email*}" ]; then
  ./scripts/vortex/notify-email.sh "$@"
fi

if [ -z "${VORTEX_NOTIFY_CHANNELS##*slack*}" ]; then
  ./scripts/vortex/notify-slack.sh "$@"
fi

if [ -z "${VORTEX_NOTIFY_CHANNELS##*newrelic*}" ]; then
  ./scripts/vortex/notify-newrelic.sh "$@"
fi

if [ -z "${VORTEX_NOTIFY_CHANNELS##*github*}" ]; then
  ./scripts/vortex/notify-github.sh "$@"
fi

if [ -z "${VORTEX_NOTIFY_CHANNELS##*jira*}" ]; then
  ./scripts/vortex/notify-jira.sh "$@"
fi

if [ -z "${VORTEX_NOTIFY_CHANNELS##*webhook*}" ]; then
  ./scripts/vortex/notify-webhook.sh "$@"
fi

pass "Finished dispatching notifications."
