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
DREVOPS_NOTIFY_WEBHOOK_URL="https://webhook.site/20094c7c-da9f-49a3-9a5f-88fef7b3f760"

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
DREVOPS_NOTIFY_WEBHOOK_MESSAGE_BODY='{"API-KEY": "API_KEY"}'

# Custom parameters and secrets to use in custom header and message body.
# Ex: [{"name": "API_KEY", "value": "XXX"},{"name": "PASSWORD", "value": "XXX"}]
DREVOPS_NOTIFY_WEBHOOK_CUSTOM_PARAMETERS_AND_SECRETS="${DREVOPS_NOTIFY_WEBHOOK_CUSTOM_PARAMETERS_AND_SECRETS:-}"
DREVOPS_NOTIFY_WEBHOOK_CUSTOM_PARAMETERS_AND_SECRETS='[{"name": "API_KEY", "value": "hehe"}]'

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
# shellcheck disable=SC2001
replace_parameters_and_secrets_in_string() {
  string="$1"
  while read -r name value; do
    string=$(echo "${string}" | sed "s/${name}/${value}/g")
  done < <(echo "${DREVOPS_NOTIFY_WEBHOOK_CUSTOM_PARAMETERS_AND_SECRETS}" | jq -r '.[] | "\(.name) \(.value)"')

  echo "${string}"
}

info "Started Webhook notification."

info "Webhook config:"
note "Environment url                       : ${DREVOPS_NOTIFY_ENVIRONMENT_URL}"
note "Webhook url                           : ${DREVOPS_NOTIFY_WEBHOOK_URL}"
note "Webhook method                        : ${DREVOPS_NOTIFY_WEBHOOK_METHOD}"
note "Webhook custom header                 :"
echo "${DREVOPS_NOTIFY_WEBHOOK_CUSTOM_HEADERS}" | jq -c '.[]' | while read -r item; do
  name=$(echo "${item}" | jq -r '.name')
  value=$(echo "${item}" | jq -r '.value')
  note "  ${name}: ${value}"
done
note "Webhook custom parameters and secrets :"
echo "${DREVOPS_NOTIFY_WEBHOOK_CUSTOM_PARAMETERS_AND_SECRETS}" | jq -c '.[]' | while read -r item; do
  name=$(echo "${item}" | jq -r '.name')
  value=$(echo "${item}" | jq -r '.value')
  note "  ${name}: ${value}"
done

# Build header.
headers_replaced=$(replace_parameters_and_secrets_in_string "${DREVOPS_NOTIFY_WEBHOOK_CUSTOM_HEADERS}")
declare -a headers
while read -r item; do
  name=$(echo "${item}" | jq -r '.name')
  value=$(echo "${item}" | jq -r '.value')
  headers+=("-H" "${name}: ${value}")
done < <(echo "${headers_replaced}" | jq -c '.[]')

# Build message body.
message_body="$(replace_parameters_and_secrets_in_string "${DREVOPS_NOTIFY_WEBHOOK_MESSAGE_BODY}")"

# Make curl request.
response_http_code="$(
  curl -s \
    -o /dev/null \
    -w '%{http_code}' \
    -X "${DREVOPS_NOTIFY_WEBHOOK_METHOD}" \
    "${headers[@]}" \
    --data "${message_body}" \
    "${DREVOPS_NOTIFY_WEBHOOK_URL}"
)"
if [[ ${response_http_code} == "200" ]]; then
  pass "Notified to webhook ${DREVOPS_NOTIFY_WEBHOOK_URL}."
else
  fail "Unable to notify to webhook ${DREVOPS_NOTIFY_WEBHOOK_URL}."
fi
