#!/usr/bin/env bash
##
# Notification dispatch to any webhook.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Deployment environment URL.
DREVOPS_NOTIFY_ENVIRONMENT_URL="${DREVOPS_NOTIFY_ENVIRONMENT_URL:-}"
DREVOPS_NOTIFY_ENVIRONMENT_URL="https://environment-example.com"

# Webhook URL.
DREVOPS_NOTIFY_WEBHOOK_URL="${DREVOPS_NOTIFY_WEBHOOK_URL:-}"
DREVOPS_NOTIFY_WEBHOOK_URL="https://example.com"

# Webhook method like POST, GET, PUT.
DREVOPS_NOTIFY_WEBHOOK_METHOD="${DREVOPS_NOTIFY_WEBHOOK_METHOD:-POST}"

# Webhook custom header as json format.
# Ex: [{"name": "Content-type", "value": "application/json"},{"name": "Authorization", "value": "Bearer API_KEY"}].
DREVOPS_NOTIFY_WEBHOOK_CUSTOM_HEADERS="${DREVOPS_NOTIFY_WEBHOOK_CUSTOM_HEADERS:-}"
DREVOPS_NOTIFY_WEBHOOK_CUSTOM_HEADERS='[{"name": "Content-type", "value": "application/json"},{"name": "Authorization", "value": "Bearer API_KEY"}]'

# Webhook message body as json format.
# This is data sent to webhook.
# Ex: {"channel": "XXX", "message": "Hello there"}.
DREVOPS_NOTIFY_WEBHOOK_MESSAGE_BODY="${DREVOPS_NOTIFY_WEBHOOK_MESSAGE_BODY:-}"
DREVOPS_NOTIFY_WEBHOOK_MESSAGE_BODY="this-is-body-API_KEY"

# Custom parameters and secrets to use in custom header and message body.
# Ex: [{"name": "API_KEY", "value": "XXX"},{"name": "PASSWORD", "value": "XXX"}]
DREVOPS_NOTIFY_WEBHOOK_CUSTOM_PARAMETERS_AND_SECRETS="${DREVOPS_NOTIFY_WEBHOOK_CUSTOM_PARAMETERS_AND_SECRETS:-}"
DREVOPS_NOTIFY_WEBHOOK_CUSTOM_PARAMETERS_AND_SECRETS='[{"name": "API_KEY", "value": "kaka"}]'

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

for cmd in php curl jq; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

# Find custom parameters and secrets in string and replace it by value.
replace_parameters_and_secrets_in_string() {
  while read -r name value; do
    string=$(echo "$1" | sed "s/$name/$value/g")
  done < <(echo "$DREVOPS_NOTIFY_WEBHOOK_CUSTOM_PARAMETERS_AND_SECRETS" | jq -r '.[] | "\(.name) \(.value)"')

  echo $string
}

info "Started Webhook notification."

info "Webhook config:"
note "Environment url                       : ${DREVOPS_NOTIFY_ENVIRONMENT_URL}"
note "Webhook url                           : ${DREVOPS_NOTIFY_WEBHOOK_URL}"
note "Webhook method                        : ${DREVOPS_NOTIFY_WEBHOOK_METHOD}"
note "Webhook custom header                 :"
echo "${DREVOPS_NOTIFY_WEBHOOK_CUSTOM_HEADERS}" | jq -c '.[]' | while read -r item; do
    name=$(echo "$item" | jq -r '.name')
    value=$(echo "$item" | jq -r '.value')
    note "  ${name}: ${value}"
done
note "Webhook custom parameters and secrets :"
echo "${DREVOPS_NOTIFY_WEBHOOK_CUSTOM_PARAMETERS_AND_SECRETS}" | jq -c '.[]' | while read -r item; do
    name=$(echo "$item" | jq -r '.name')
    value=$(echo "$item" | jq -r '.value')
    note "  ${name}: ${value}"
done

# Build header.
declare -a headers
while IFS=: read -r name value; do
    # Add header to the curl_headers array
    headers+=("-H" "$name: $value")
done < <(echo "$(replace_parameters_and_secrets_in_string ${DREVOPS_NOTIFY_WEBHOOK_CUSTOM_HEADERS})" | jq -r '.[] | "\(.name): \(.value)"')

# Build message body.
message_body=$(replace_parameters_and_secrets_in_string "${DREVOPS_NOTIFY_WEBHOOK_MESSAGE_BODY}")

# Make curl request with headers
curl_command="curl -s -X ${DREVOPS_NOTIFY_WEBHOOK_METHOD} ${headers[@]} --data ${message_body} ${DREVOPS_NOTIFY_WEBHOOK_URL}"
echo $curl_command
