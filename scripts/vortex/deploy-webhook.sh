#!/usr/bin/env bash
##
# Deploy by calling a webhook.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# The URL of the webhook to call.
VORTEX_DEPLOY_WEBHOOK_URL="${VORTEX_DEPLOY_WEBHOOK_URL:-}"

# Webhook call method.
VORTEX_DEPLOY_WEBHOOK_METHOD="${VORTEX_DEPLOY_WEBHOOK_METHOD:-GET}"

# The status code of the expected response.
VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS="${VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS:-200}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

#shellcheck disable=SC2043
for cmd in curl; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

info "Started WEBHOOK deployment."

# Check all required values.
[ -z "${VORTEX_DEPLOY_WEBHOOK_URL}" ] && fail "Missing required value for VORTEX_DEPLOY_WEBHOOK_URL." && exit 1
[ -z "${VORTEX_DEPLOY_WEBHOOK_METHOD}" ] && fail "Missing required value for VORTEX_DEPLOY_WEBHOOK_METHOD." && exit 1
[ -z "${VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS}" ] && fail "Missing required value for VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS." && exit 1

if curl --request "${VORTEX_DEPLOY_WEBHOOK_METHOD}" --location --silent --output /dev/null --write-out "%{http_code}" "${VORTEX_DEPLOY_WEBHOOK_URL}" | grep --quiet "${VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS}"; then
  note "Webhook call completed."
else
  fail "Unable to complete webhook deployment."
  exit 1
fi

pass "Finished WEBHOOK deployment."
