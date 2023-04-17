#!/usr/bin/env bash
##
# Notify about events.
#
# This is a router script to call relevant scripts based on type.
#
# Dynamic environment variables are passed from the callers.
# Constant environment variables are expected to be set through UI.
#

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The type of test.
# Can be a combination of comma-separated values: email,newrelic,github,jira
# Set as a constant variable.
DREVOPS_NOTIFY_TYPE="${DREVOPS_NOTIFY_TYPE:-email}"

# The event to notify about. Can be 'pre_deployment' or 'post_deployment'.
DREVOPS_NOTIFY_EVENT="${DREVOPS_NOTIFY_EVENT:-post_deployment}"

# Flag to skip running of all notifications.
DREVOPS_NOTIFY_SKIP="${DREVOPS_NOTIFY_SKIP:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started dispatching notifications."

[ -n "${DREVOPS_NOTIFY_SKIP}" ] && pass "Skipping dispatching notifications." && exit 0

# Narrow-down the notification type based on the event.
# @note This logic may be moved into notification scripts in the future.
if [ "${DREVOPS_NOTIFY_EVENT}" == "pre_deployment" ]; then
  DREVOPS_NOTIFY_TYPE="github"
elif [ "${DREVOPS_NOTIFY_EVENT}" == "post_deployment" ]; then
  # Preserve the value.
  true
else
  fail "Unsupported event ${DREVOPS_NOTIFY_EVENT} provided." && exit 1
fi

if [ -z "${DREVOPS_NOTIFY_TYPE##*email*}" ]; then
  php ./scripts/drevops/notify-email.php "$@"
fi

if [ -z "${DREVOPS_NOTIFY_TYPE##*newrelic*}" ]; then
  ./scripts/drevops/notify-newrelic.sh "$@"
fi

if [ -z "${DREVOPS_NOTIFY_TYPE##*github*}" ]; then
  ./scripts/drevops/notify-github.sh "$@"
fi

if [ -z "${DREVOPS_NOTIFY_TYPE##*jira*}" ]; then
  ./scripts/drevops/notify-jira.sh "$@"
fi

pass "Finished dispatching notifications."
