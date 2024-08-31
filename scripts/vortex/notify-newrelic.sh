#!/usr/bin/env bash
##
# Notification dispatch to New Relic.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Project name to notify.
VORTEX_NOTIFY_NEWRELIC_PROJECT="${VORTEX_NOTIFY_NEWRELIC_PROJECT:-${VORTEX_NOTIFY_PROJECT:-}}"

# NewRelic API key, usually of type 'USER'.
VORTEX_NOTIFY_NEWRELIC_APIKEY="${VORTEX_NOTIFY_NEWRELIC_APIKEY:-}"

# Deployment reference, such as a git branch or pr.
VORTEX_NOTIFY_NEWRELIC_REF="${VORTEX_NOTIFY_NEWRELIC_REF:-${VORTEX_NOTIFY_REF:-}}"

# Deployment commit reference, such as a git SHA.
VORTEX_NOTIFY_NEWRELIC_SHA="${VORTEX_NOTIFY_NEWRELIC_SHA:-${VORTEX_NOTIFY_SHA:-}}"

# NewRelic application name as it appears in the dashboard.
VORTEX_NOTIFY_NEWRELIC_APP_NAME="${VORTEX_NOTIFY_NEWRELIC_APP_NAME:-"${VORTEX_NOTIFY_NEWRELIC_PROJECT}-${VORTEX_NOTIFY_NEWRELIC_REF}"}"

# Optional NewRelic Application ID.
#
# Will be discovered automatically from application name if not provided.
VORTEX_NOTIFY_NEWRELIC_APPID="${VORTEX_NOTIFY_NEWRELIC_APPID:-}"

# Optional NewRelic notification description.
VORTEX_NOTIFY_NEWRELIC_DESCRIPTION="${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION:-"${VORTEX_NOTIFY_NEWRELIC_REF} deployed"}"

# Optional NewRelic notification changelog.
#
# Defaults to the description.
VORTEX_NOTIFY_NEWRELIC_CHANGELOG="${VORTEX_NOTIFY_NEWRELIC_CHANGELOG:-${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}}"

# Optional name of the user performing the deployment.
VORTEX_NOTIFY_NEWRELIC_USER="${VORTEX_NOTIFY_NEWRELIC_USER:-"Deployment robot"}"

# Optional NewRelic endpoint.
VORTEX_NOTIFY_NEWRELIC_ENDPOINT="${VORTEX_NOTIFY_NEWRELIC_ENDPOINT:-https://api.newrelic.com/v2}"

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

[ -z "${VORTEX_NOTIFY_NEWRELIC_PROJECT}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_PROJECT" && exit 1
[ -z "${VORTEX_NOTIFY_NEWRELIC_APIKEY}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_APIKEY" && exit 1
[ -z "${VORTEX_NOTIFY_NEWRELIC_REF}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_REF" && exit 1
[ -z "${VORTEX_NOTIFY_NEWRELIC_SHA}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_SHA" && exit 1
[ -z "${VORTEX_NOTIFY_NEWRELIC_APP_NAME}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_APP_NAME" && exit 1
[ -z "${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_DESCRIPTION" && exit 1
[ -z "${VORTEX_NOTIFY_NEWRELIC_CHANGELOG}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_CHANGELOG" && exit 1
[ -z "${VORTEX_NOTIFY_NEWRELIC_USER}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_USER" && exit 1

info "Started New Relic notification."

# Discover APP id by name if it was not provided.
if [ -z "${VORTEX_NOTIFY_NEWRELIC_APPID}" ] && [ -n "${VORTEX_NOTIFY_NEWRELIC_APP_NAME}" ]; then
  VORTEX_NOTIFY_NEWRELIC_APPID="$(curl -s -X GET "${VORTEX_NOTIFY_NEWRELIC_ENDPOINT}/applications.json" \
    -H "Api-Key:${VORTEX_NOTIFY_NEWRELIC_APIKEY}" \
    -s -G -d "filter[name]=${VORTEX_NOTIFY_NEWRELIC_APP_NAME}&exclude_links=true" |
    cut -c 24- |
    cut -c -10)"
fi

# Check if the length of the VORTEX_NOTIFY_NEWRELIC_APPID variable is not 10 OR
# if the variable doesn't contain only numeric values and exit.
{ [ "${#VORTEX_NOTIFY_NEWRELIC_APPID}" != "10" ] || [ "$(expr "x${VORTEX_NOTIFY_NEWRELIC_APPID}" : "x[0-9]*$")" -eq 0 ]; } && note "Notification skipped: No New Relic application ID found for ${VORTEX_NOTIFY_NEWRELIC_APP_NAME}. This is expected for non-configured environments." && exit 0

if ! curl -X POST "${VORTEX_NOTIFY_NEWRELIC_ENDPOINT}/applications/${VORTEX_NOTIFY_NEWRELIC_APPID}/deployments.json" \
  -L -s -o /dev/null -w "%{http_code}" \
  -H "Api-Key:${VORTEX_NOTIFY_NEWRELIC_APIKEY}" \
  -H 'Content-Type: application/json' \
  -d \
  "{
  \"deployment\": {
    \"revision\": \"${VORTEX_NOTIFY_NEWRELIC_SHA}\",
    \"changelog\": \"${VORTEX_NOTIFY_NEWRELIC_CHANGELOG}\",
    \"description\": \"${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}\",
    \"user\": \"${VORTEX_NOTIFY_NEWRELIC_USER}\"
  }
}" | grep -q '201'; then
  fail "[ERROR] Failed to crate a deployment notification for application ${VORTEX_NOTIFY_NEWRELIC_APP_NAME} with ID ${VORTEX_NOTIFY_NEWRELIC_APPID}"
  exit 1
fi

pass "Finished New Relic notification."
