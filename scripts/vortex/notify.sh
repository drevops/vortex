#!/usr/bin/env bash
##
# Notify about events.
#
# This is a router script to call relevant scripts based on type.
#
# Dynamic environment variables are passed from the callers.
# Constant environment variables are expected to be set explicitly.
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

# Notification pull request number.
VORTEX_NOTIFY_PR_NUMBER="${VORTEX_NOTIFY_PR_NUMBER:-}"

# Notification git commit SHA.
VORTEX_NOTIFY_SHA="${VORTEX_NOTIFY_SHA:-}"

# Notification deployment environment URL.
VORTEX_NOTIFY_ENVIRONMENT_URL="${VORTEX_NOTIFY_ENVIRONMENT_URL:-}"

# Notification environment type: production, uat, dev, pr.
VORTEX_NOTIFY_ENVIRONMENT_TYPE="${VORTEX_NOTIFY_ENVIRONMENT_TYPE:-}"

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

# Validate event type (scripts will handle event-specific logic).
if [ "${VORTEX_NOTIFY_EVENT}" != "pre_deployment" ] && [ "${VORTEX_NOTIFY_EVENT}" != "post_deployment" ]; then
  fail "Unsupported event ${VORTEX_NOTIFY_EVENT} provided." && exit 1
fi

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
