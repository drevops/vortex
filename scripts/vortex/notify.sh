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

# The channels of the notifications.
#
# Can be a combination of comma-separated values: email,newrelic,github,jira
VORTEX_NOTIFY_CHANNELS="${VORTEX_NOTIFY_CHANNELS:-email}"

# The event to notify about.
#
# Can be only 'pre_deployment' or 'post_deployment'. Used internally.
VORTEX_NOTIFY_EVENT="${VORTEX_NOTIFY_EVENT:-post_deployment}"

# Flag to skip running of all notifications.
VORTEX_NOTIFY_SKIP="${VORTEX_NOTIFY_SKIP:-}"

# The project to notify about.
VORTEX_NOTIFY_PROJECT="${VORTEX_NOTIFY_PROJECT:-${VORTEX_PROJECT:-}}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started dispatching notifications."

[ -n "${VORTEX_NOTIFY_SKIP:-}" ] && pass "Skipping dispatching notifications." && exit 0

# Narrow-down the notification type based on the event.
# @note This logic may be moved into notification scripts in the future.
if [ "${VORTEX_NOTIFY_EVENT:-}" == "pre_deployment" ]; then
  VORTEX_NOTIFY_CHANNELS="github"
elif [ "${VORTEX_NOTIFY_EVENT:-}" == "post_deployment" ]; then
  # Preserve the value.
  true
else
  fail "Unsupported event ${VORTEX_NOTIFY_EVENT} provided." && exit 1
fi

if [ -z "${VORTEX_NOTIFY_CHANNELS##*email*}" ]; then
  ./scripts/vortex/notify-email.sh "$@"
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
