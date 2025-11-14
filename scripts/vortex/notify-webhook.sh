#!/usr/bin/env bash
##
# Notification dispatch to any webhook.
#
# shellcheck disable=SC1090,SC1091,SC2016

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Webhook notification project name.
VORTEX_NOTIFY_WEBHOOK_PROJECT="${VORTEX_NOTIFY_WEBHOOK_PROJECT:-${VORTEX_NOTIFY_PROJECT:-}}"

# Webhook notification deployment label (branch name, PR number, or custom identifier).
VORTEX_NOTIFY_WEBHOOK_LABEL="${VORTEX_NOTIFY_WEBHOOK_LABEL:-${VORTEX_NOTIFY_LABEL:-}}"

# Webhook notification environment URL.
VORTEX_NOTIFY_WEBHOOK_ENVIRONMENT_URL="${VORTEX_NOTIFY_WEBHOOK_ENVIRONMENT_URL:-${VORTEX_NOTIFY_ENVIRONMENT_URL:-}}"

# Webhook notification login URL.
VORTEX_NOTIFY_WEBHOOK_LOGIN_URL="${VORTEX_NOTIFY_WEBHOOK_LOGIN_URL:-${VORTEX_NOTIFY_LOGIN_URL:-}}"

# Webhook notification event type. Can be 'pre_deployment' or 'post_deployment'.
VORTEX_NOTIFY_WEBHOOK_EVENT="${VORTEX_NOTIFY_WEBHOOK_EVENT:-${VORTEX_NOTIFY_EVENT:-post_deployment}}"

# Webhook notification endpoint URL.
VORTEX_NOTIFY_WEBHOOK_URL="${VORTEX_NOTIFY_WEBHOOK_URL:-}"

# Webhook notification HTTP method like POST, GET, PUT.
VORTEX_NOTIFY_WEBHOOK_METHOD="${VORTEX_NOTIFY_WEBHOOK_METHOD:-POST}"

# Webhook notification pipe-separated headers.
# Separate multiple headers with a pipe `|`.
# Example: `Content-type: application/json|Authorization: Bearer API_KEY`.
VORTEX_NOTIFY_WEBHOOK_HEADERS="${VORTEX_NOTIFY_WEBHOOK_HEADERS:-Content-type: application/json}"

# Webhook notification JSON payload.
# Available tokens: %message%, %project%, %label%, %timestamp%, %environment_url%, %login_url%
VORTEX_NOTIFY_WEBHOOK_PAYLOAD="${VORTEX_NOTIFY_WEBHOOK_PAYLOAD:-}"

# Webhook notification expected HTTP status.
VORTEX_NOTIFY_WEBHOOK_RESPONSE_STATUS="${VORTEX_NOTIFY_WEBHOOK_RESPONSE_STATUS:-200}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

for cmd in php curl; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

[ -z "${VORTEX_NOTIFY_WEBHOOK_PROJECT}" ] && fail "Missing required value for VORTEX_NOTIFY_WEBHOOK_PROJECT" && exit 1
[ -z "${VORTEX_NOTIFY_WEBHOOK_LABEL}" ] && fail "Missing required value for VORTEX_NOTIFY_WEBHOOK_LABEL" && exit 1
[ -z "${VORTEX_NOTIFY_WEBHOOK_ENVIRONMENT_URL}" ] && fail "Missing required value for VORTEX_NOTIFY_WEBHOOK_ENVIRONMENT_URL" && exit 1
[ -z "${VORTEX_NOTIFY_WEBHOOK_LOGIN_URL}" ] && fail "Missing required value for VORTEX_NOTIFY_WEBHOOK_LOGIN_URL" && exit 1
[ -z "${VORTEX_NOTIFY_WEBHOOK_URL}" ] && fail "Missing required value for VORTEX_NOTIFY_WEBHOOK_URL" && exit 1
[ -z "${VORTEX_NOTIFY_WEBHOOK_METHOD}" ] && fail "Missing required value for VORTEX_NOTIFY_WEBHOOK_METHOD" && exit 1
[ -z "${VORTEX_NOTIFY_WEBHOOK_HEADERS}" ] && fail "Missing required value for VORTEX_NOTIFY_WEBHOOK_HEADERS" && exit 1

info "Started Webhook notification."

# Skip if this is a pre-deployment event (webhook only for post-deployment).
if [ "${VORTEX_NOTIFY_WEBHOOK_EVENT}" = "pre_deployment" ]; then
  pass "Skipping Webhook notification for pre_deployment event."
  exit 0
fi

# Set default payload template if not provided.
if [ -z "${VORTEX_NOTIFY_WEBHOOK_PAYLOAD}" ]; then
  VORTEX_NOTIFY_WEBHOOK_PAYLOAD='{"channel": "Channel 1", "message": "%message%", "project": "%project%", "label": "%label%", "timestamp": "%timestamp%", "environment_url": "%environment_url%", "login_url": "%login_url%"}'
fi

# Build and replace tokens (%variable_name%) for webhook payload.
timestamp=$(date '+%d/%m/%Y %H:%M:%S %Z')
message='## This is an automated message ##\nSite %project% %label% has been deployed at %timestamp% and is available at %environment_url%.\nLogin at: %login_url%'

# JSON-escape each replacement value before substituting into JSON template.
# shellcheck disable=SC2016
VORTEX_NOTIFY_WEBHOOK_PAYLOAD=$(REPLACEMENT="${message}" TEMPLATE="${VORTEX_NOTIFY_WEBHOOK_PAYLOAD}" php -r '$escaped = json_encode(getenv("REPLACEMENT")); $escaped = substr($escaped, 1, -1); echo str_replace("%message%", $escaped, getenv("TEMPLATE"));')
VORTEX_NOTIFY_WEBHOOK_PAYLOAD=$(REPLACEMENT="${timestamp}" TEMPLATE="${VORTEX_NOTIFY_WEBHOOK_PAYLOAD}" php -r '$escaped = json_encode(getenv("REPLACEMENT")); $escaped = substr($escaped, 1, -1); echo str_replace("%timestamp%", $escaped, getenv("TEMPLATE"));')
VORTEX_NOTIFY_WEBHOOK_PAYLOAD=$(REPLACEMENT="${VORTEX_NOTIFY_WEBHOOK_LABEL}" TEMPLATE="${VORTEX_NOTIFY_WEBHOOK_PAYLOAD}" php -r '$escaped = json_encode(getenv("REPLACEMENT")); $escaped = substr($escaped, 1, -1); echo str_replace("%label%", $escaped, getenv("TEMPLATE"));')
VORTEX_NOTIFY_WEBHOOK_PAYLOAD=$(REPLACEMENT="${VORTEX_NOTIFY_WEBHOOK_PROJECT}" TEMPLATE="${VORTEX_NOTIFY_WEBHOOK_PAYLOAD}" php -r '$escaped = json_encode(getenv("REPLACEMENT")); $escaped = substr($escaped, 1, -1); echo str_replace("%project%", $escaped, getenv("TEMPLATE"));')
VORTEX_NOTIFY_WEBHOOK_PAYLOAD=$(REPLACEMENT="${VORTEX_NOTIFY_WEBHOOK_ENVIRONMENT_URL}" TEMPLATE="${VORTEX_NOTIFY_WEBHOOK_PAYLOAD}" php -r '$escaped = json_encode(getenv("REPLACEMENT")); $escaped = substr($escaped, 1, -1); echo str_replace("%environment_url%", $escaped, getenv("TEMPLATE"));')
VORTEX_NOTIFY_WEBHOOK_PAYLOAD=$(REPLACEMENT="${VORTEX_NOTIFY_WEBHOOK_LOGIN_URL}" TEMPLATE="${VORTEX_NOTIFY_WEBHOOK_PAYLOAD}" php -r '$escaped = json_encode(getenv("REPLACEMENT")); $escaped = substr($escaped, 1, -1); echo str_replace("%login_url%", $escaped, getenv("TEMPLATE"));')

# Sanitize webhook URL (extract domain, hide path that may contain secrets).
webhook_domain=$(echo "${VORTEX_NOTIFY_WEBHOOK_URL}" | sed -E 's|(https?://[^/]+).*|\1|')

info "Webhook notification summary:"
note "Project            : ${VORTEX_NOTIFY_WEBHOOK_PROJECT}"
note "Deployment         : ${VORTEX_NOTIFY_WEBHOOK_LABEL}"
note "Environment URL    : ${VORTEX_NOTIFY_WEBHOOK_ENVIRONMENT_URL}"
note "Login URL          : ${VORTEX_NOTIFY_WEBHOOK_LOGIN_URL}"
note "Webhook URL        : ${webhook_domain}/***"
note "Method             : ${VORTEX_NOTIFY_WEBHOOK_METHOD}"
note "Headers            : ${VORTEX_NOTIFY_WEBHOOK_HEADERS}"
note "Expected Status    : ${VORTEX_NOTIFY_WEBHOOK_RESPONSE_STATUS}"
note "Payload (first 200): ${VORTEX_NOTIFY_WEBHOOK_PAYLOAD:0:200}..."

# Build headers.
headers=()
IFS=\| read -ra webhook_headers <<<"${VORTEX_NOTIFY_WEBHOOK_HEADERS}"
for item in "${webhook_headers[@]}"; do
  headers+=('-H' "${item}")
done

# Make curl request.
if ! curl -L -s -o /dev/null -w '%{http_code}' \
  -X "${VORTEX_NOTIFY_WEBHOOK_METHOD}" \
  "${headers[@]}" \
  -d "${VORTEX_NOTIFY_WEBHOOK_PAYLOAD}" \
  "${VORTEX_NOTIFY_WEBHOOK_URL}" | grep -q "${VORTEX_NOTIFY_WEBHOOK_RESPONSE_STATUS}"; then
  fail "Unable to send notification to webhook ${VORTEX_NOTIFY_WEBHOOK_URL}."
  exit 1
fi

pass "Finished Webhook notification."
