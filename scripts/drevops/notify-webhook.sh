#!/usr/bin/env bash
##
# Notification dispatch to any webhook.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Project name to notify.
DREVOPS_NOTIFY_PROJECT="${DREVOPS_NOTIFY_PROJECT:-}"

# Git reference to notify about.
DREVOPS_NOTIFY_REF="${DREVOPS_NOTIFY_REF:-}"

# Deployment environment URL.
DREVOPS_NOTIFY_ENVIRONMENT_URL="${DREVOPS_NOTIFY_ENVIRONMENT_URL:-}"

# Webhook URL.
DREVOPS_NOTIFY_WEBHOOK_URL="${DREVOPS_NOTIFY_WEBHOOK_URL:-}"

# Webhook method like POST, GET, PUT.
DREVOPS_NOTIFY_WEBHOOK_METHOD="${DREVOPS_NOTIFY_WEBHOOK_METHOD:-POST}"

# Webhook headers.
# Separate multiple headers with a pipe `|`.
# Example: `Content-type: application/json|Authorization: Bearer API_KEY`.
DREVOPS_NOTIFY_WEBHOOK_HEADERS="${DREVOPS_NOTIFY_WEBHOOK_HEADERS:-Content-type: application/json}"

# Webhook message body as json format.
DREVOPS_NOTIFY_WEBHOOK_PAYLOAD="${DREVOPS_NOTIFY_WEBHOOK_PAYLOAD:-{\"channel\": \"Channel 1\", \"message\": \"%message%\", \"project\": \"%project%\", \"ref\": \"%ref%\", \"timestamp\": \"%timestamp%\", \"environment_url\": \"%environment_url%\"}}"

# The pattern of response code return by curl.
DREVOPS_NOTIFY_WEBHOOK_RESPONSE_STATUS="${DREVOPS_NOTIFY_WEBHOOK_RESPONSE_STATUS:-200}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

for cmd in php curl; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

[ -z "${DREVOPS_NOTIFY_PROJECT}" ] && fail "Missing required value for DREVOPS_NOTIFY_PROJECT" && exit 1
[ -z "${DREVOPS_NOTIFY_REF}" ] && fail "Missing required value for DREVOPS_NOTIFY_REF" && exit 1
[ -z "${DREVOPS_NOTIFY_ENVIRONMENT_URL}" ] && fail "Missing required value for DREVOPS_NOTIFY_ENVIRONMENT_URL" && exit 1
[ -z "${DREVOPS_NOTIFY_WEBHOOK_URL}" ] && fail "Missing required value for DREVOPS_NOTIFY_WEBHOOK_URL" && exit 1
[ -z "${DREVOPS_NOTIFY_WEBHOOK_METHOD}" ] && fail "Missing required value for DREVOPS_NOTIFY_WEBHOOK_METHOD" && exit 1
[ -z "${DREVOPS_NOTIFY_WEBHOOK_HEADERS}" ] && fail "Missing required value for DREVOPS_NOTIFY_WEBHOOK_HEADERS" && exit 1
[ -z "${DREVOPS_NOTIFY_WEBHOOK_PAYLOAD}" ] && fail "Missing required value for DREVOPS_NOTIFY_WEBHOOK_PAYLOAD" && exit 1

# Build and replace some variables (%variable_name%) for webhook payload.
timestamp=$(date '+%d/%m/%Y %H:%M:%S %Z')
message='## This is an automated message ##\nSite %project% \"%ref%\" branch has been deployed at %timestamp% and is available at %environment_url%.\nLogin at: %environment_url%/user/login'

DREVOPS_NOTIFY_WEBHOOK_PAYLOAD=$(php -r "echo str_replace('%message%', '${message}', '${DREVOPS_NOTIFY_WEBHOOK_PAYLOAD}');")
DREVOPS_NOTIFY_WEBHOOK_PAYLOAD=$(php -r "echo str_replace('%timestamp%', '${timestamp}', '${DREVOPS_NOTIFY_WEBHOOK_PAYLOAD}');")
DREVOPS_NOTIFY_WEBHOOK_PAYLOAD=$(php -r "echo str_replace('%ref%', '${DREVOPS_NOTIFY_REF}', '${DREVOPS_NOTIFY_WEBHOOK_PAYLOAD}');")
DREVOPS_NOTIFY_WEBHOOK_PAYLOAD=$(php -r "echo str_replace('%project%', '${DREVOPS_NOTIFY_PROJECT}', '${DREVOPS_NOTIFY_WEBHOOK_PAYLOAD}');")
DREVOPS_NOTIFY_WEBHOOK_PAYLOAD=$(php -r "echo str_replace('%environment_url%', '${DREVOPS_NOTIFY_ENVIRONMENT_URL}', '${DREVOPS_NOTIFY_WEBHOOK_PAYLOAD}');")

info "Started Webhook notification."

info "Webhook config:"
note "Project                               : ${DREVOPS_NOTIFY_PROJECT}"
note "Ref                                   : ${DREVOPS_NOTIFY_REF}"
note "Environment url                       : ${DREVOPS_NOTIFY_ENVIRONMENT_URL}"
note "Webhook url                           : ${DREVOPS_NOTIFY_WEBHOOK_URL}"
note "Webhook method                        : ${DREVOPS_NOTIFY_WEBHOOK_METHOD}"
note "Webhook headers                       : ${DREVOPS_NOTIFY_WEBHOOK_HEADERS}"
note "Webhook payload                       : ${DREVOPS_NOTIFY_WEBHOOK_PAYLOAD}"
note "Webhook response status               : ${DREVOPS_NOTIFY_WEBHOOK_RESPONSE_STATUS}"

# Build headers.
headers=()
IFS=\| read -ra webhook_headers <<<"${DREVOPS_NOTIFY_WEBHOOK_HEADERS}"
for item in "${webhook_headers[@]}"; do
  headers+=('-H' "${item}")
done

# Make curl request.
if ! curl -L -s -o /dev/null -w '%{http_code}' \
  -X "${DREVOPS_NOTIFY_WEBHOOK_METHOD}" \
  "${headers[@]}" \
  -d "${DREVOPS_NOTIFY_WEBHOOK_PAYLOAD}" \
  "${DREVOPS_NOTIFY_WEBHOOK_URL}" | grep -q "${DREVOPS_NOTIFY_WEBHOOK_RESPONSE_STATUS}"; then
  fail "Unable to send notification to webhook ${DREVOPS_NOTIFY_WEBHOOK_URL}."
  exit 1
fi

pass "Finished Webhook notification."
