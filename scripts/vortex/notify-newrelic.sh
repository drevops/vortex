#!/usr/bin/env bash
##
# Notification dispatch to New Relic.
#
# shellcheck disable=SC1090,SC1091,SC2043

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Flag to enable New Relic notifications.
# Set to "true" (not "1") in environments where New Relic is configured.
VORTEX_NOTIFY_NEWRELIC_ENABLED="${VORTEX_NOTIFY_NEWRELIC_ENABLED:-${NEWRELIC_ENABLED:-}}"

# New Relic notification project name.
VORTEX_NOTIFY_NEWRELIC_PROJECT="${VORTEX_NOTIFY_NEWRELIC_PROJECT:-${VORTEX_NOTIFY_PROJECT:-}}"

# New Relic notification User API Key.
#
# To obtain your User API Key:
# 1. Log in to New Relic
# 2. Click on your profile icon (bottom left)
# 3. Go to "API keys"
# 4. Create or copy an existing "User key"
# 5. The key format is: NRAK-XXXXXXXXXXXXXXXXXXXXXX
#
# @see https://docs.newrelic.com/docs/apis/intro-apis/new-relic-api-keys/#user-key
# @see https://www.vortextemplate.com/docs/deployment/notifications#new-relic
VORTEX_NOTIFY_NEWRELIC_USER_KEY="${VORTEX_NOTIFY_NEWRELIC_USER_KEY:-${NEWRELIC_USER_KEY:-}}"

# New Relic notification deployment label (human-readable identifier for display).
VORTEX_NOTIFY_NEWRELIC_LABEL="${VORTEX_NOTIFY_NEWRELIC_LABEL:-${VORTEX_NOTIFY_LABEL:-}}"

# New Relic notification git commit SHA.
VORTEX_NOTIFY_NEWRELIC_SHA="${VORTEX_NOTIFY_NEWRELIC_SHA:-${VORTEX_NOTIFY_SHA:-}}"

# New Relic notification deployment revision.
# If not provided, will use SHA if available, otherwise auto-generated.
VORTEX_NOTIFY_NEWRELIC_REVISION="${VORTEX_NOTIFY_NEWRELIC_REVISION:-}"

# New Relic notification environment URL.
VORTEX_NOTIFY_NEWRELIC_ENVIRONMENT_URL="${VORTEX_NOTIFY_NEWRELIC_ENVIRONMENT_URL:-${VORTEX_NOTIFY_ENVIRONMENT_URL:-}}"

# New Relic notification login URL.
VORTEX_NOTIFY_NEWRELIC_LOGIN_URL="${VORTEX_NOTIFY_NEWRELIC_LOGIN_URL:-${VORTEX_NOTIFY_LOGIN_URL:-}}"

# New Relic notification application name as it appears in the dashboard.
VORTEX_NOTIFY_NEWRELIC_APP_NAME="${VORTEX_NOTIFY_NEWRELIC_APP_NAME:-"${VORTEX_NOTIFY_NEWRELIC_PROJECT}-${VORTEX_NOTIFY_NEWRELIC_LABEL}"}"

# New Relic notification application ID (auto-discovered if not provided).
#
# Will be discovered automatically from application name if not provided.
VORTEX_NOTIFY_NEWRELIC_APPID="${VORTEX_NOTIFY_NEWRELIC_APPID:-}"

# New Relic notification deployment description template.
# Available tokens: %project%, %label%, %timestamp%, %environment_url%, %login_url%
VORTEX_NOTIFY_NEWRELIC_DESCRIPTION="${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION:-}"

# New Relic notification deployment changelog.
# Defaults to the description.
VORTEX_NOTIFY_NEWRELIC_CHANGELOG="${VORTEX_NOTIFY_NEWRELIC_CHANGELOG:-}"

# New Relic notification user performing deployment.
VORTEX_NOTIFY_NEWRELIC_USER="${VORTEX_NOTIFY_NEWRELIC_USER:-"Deployment robot"}"

# New Relic notification API endpoint.
VORTEX_NOTIFY_NEWRELIC_ENDPOINT="${VORTEX_NOTIFY_NEWRELIC_ENDPOINT:-https://api.newrelic.com/v2}"

# New Relic notification event type. Can be 'pre_deployment' or 'post_deployment'.
VORTEX_NOTIFY_NEWRELIC_EVENT="${VORTEX_NOTIFY_NEWRELIC_EVENT:-${VORTEX_NOTIFY_EVENT:-post_deployment}}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

if [ -z "${VORTEX_NOTIFY_NEWRELIC_ENABLED}" ]; then
  info "New Relic is not enabled. Set NEWRELIC_ENABLED or VORTEX_NOTIFY_NEWRELIC_ENABLED in your environment."
  exit 0
fi

for cmd in curl; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

[ -z "${VORTEX_NOTIFY_NEWRELIC_PROJECT}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_PROJECT" && exit 1
[ -z "${VORTEX_NOTIFY_NEWRELIC_USER_KEY}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_USER_KEY" && exit 1
[ -z "${VORTEX_NOTIFY_NEWRELIC_LABEL}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_LABEL" && exit 1
[ -z "${VORTEX_NOTIFY_NEWRELIC_APP_NAME}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_APP_NAME" && exit 1
[ -z "${VORTEX_NOTIFY_NEWRELIC_USER}" ] && fail "Missing required value for VORTEX_NOTIFY_NEWRELIC_USER" && exit 1

info "Started New Relic notification."

# Auto-generate revision if not provided.
# Use SHA if available, otherwise fall back to LABEL-TIMESTAMP.
if [ -z "${VORTEX_NOTIFY_NEWRELIC_REVISION}" ]; then
  if [ -n "${VORTEX_NOTIFY_NEWRELIC_SHA}" ]; then
    VORTEX_NOTIFY_NEWRELIC_REVISION="${VORTEX_NOTIFY_NEWRELIC_SHA}"
  else
    revision_date=$(date '+%Y%m%d')
    revision_time=$(date '+%H%M%S')
    VORTEX_NOTIFY_NEWRELIC_REVISION="${VORTEX_NOTIFY_NEWRELIC_LABEL}-${revision_date}-${revision_time}"
  fi
  note "Auto-generated revision: ${VORTEX_NOTIFY_NEWRELIC_REVISION}"
fi

# Skip if this is a pre-deployment event (New Relic only for post-deployment).
if [ "${VORTEX_NOTIFY_NEWRELIC_EVENT}" = "pre_deployment" ]; then
  pass "Skipping New Relic notification for pre_deployment event."
  exit 0
fi

# Set default description template if not provided.
if [ -z "${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}" ]; then
  VORTEX_NOTIFY_NEWRELIC_DESCRIPTION="Site %project% %label% has been deployed at %timestamp% and is available at %environment_url%"
fi

# Build message by replacing tokens.
timestamp=$(date '+%d/%m/%Y %H:%M:%S %Z')

# Replace tokens in description template.
VORTEX_NOTIFY_NEWRELIC_DESCRIPTION=$(REPLACEMENT="${VORTEX_NOTIFY_NEWRELIC_PROJECT}" TEMPLATE="${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}" php -r 'echo str_replace("%project%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
VORTEX_NOTIFY_NEWRELIC_DESCRIPTION=$(REPLACEMENT="${VORTEX_NOTIFY_NEWRELIC_LABEL}" TEMPLATE="${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}" php -r 'echo str_replace("%label%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
VORTEX_NOTIFY_NEWRELIC_DESCRIPTION=$(REPLACEMENT="${timestamp}" TEMPLATE="${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}" php -r 'echo str_replace("%timestamp%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
VORTEX_NOTIFY_NEWRELIC_DESCRIPTION=$(REPLACEMENT="${VORTEX_NOTIFY_NEWRELIC_ENVIRONMENT_URL}" TEMPLATE="${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}" php -r 'echo str_replace("%environment_url%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
VORTEX_NOTIFY_NEWRELIC_DESCRIPTION=$(REPLACEMENT="${VORTEX_NOTIFY_NEWRELIC_LOGIN_URL}" TEMPLATE="${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}" php -r 'echo str_replace("%login_url%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')

# Build changelog if not provided (defaults to description).
if [ -z "${VORTEX_NOTIFY_NEWRELIC_CHANGELOG}" ]; then
  VORTEX_NOTIFY_NEWRELIC_CHANGELOG="${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}"
fi

task "Discovering APP id by name if it was not provided."
if [ -z "${VORTEX_NOTIFY_NEWRELIC_APPID}" ] && [ -n "${VORTEX_NOTIFY_NEWRELIC_APP_NAME}" ]; then
  VORTEX_NOTIFY_NEWRELIC_APPID="$(curl -s -X GET "${VORTEX_NOTIFY_NEWRELIC_ENDPOINT}/applications.json" \
    -H "Api-Key: ${VORTEX_NOTIFY_NEWRELIC_USER_KEY}" \
    -s -G -d "filter[name]=${VORTEX_NOTIFY_NEWRELIC_APP_NAME}&exclude_links=true" |
    php -r "\$data = json_decode(file_get_contents('php://stdin'), TRUE); if (isset(\$data['applications'][0]['id'])) { echo \$data['applications'][0]['id']; }")"
fi

# Check if the VORTEX_NOTIFY_NEWRELIC_APPID variable is empty OR
# if the variable doesn't contain only numeric values and exit.
task "Checking if the application ID is valid."
if [ -z "${VORTEX_NOTIFY_NEWRELIC_APPID}" ] || [ "$(expr "x${VORTEX_NOTIFY_NEWRELIC_APPID}" : "x[0-9]*$")" -eq 0 ]; then
  note "Notification skipped: No New Relic application ID found for ${VORTEX_NOTIFY_NEWRELIC_APP_NAME}. This is expected for non-configured environments."
  exit 0
fi

info "New Relic notification summary:"
note "Project          : ${VORTEX_NOTIFY_NEWRELIC_PROJECT}"
note "Deployment       : ${VORTEX_NOTIFY_NEWRELIC_LABEL}"
note "App Name         : ${VORTEX_NOTIFY_NEWRELIC_APP_NAME}"
note "App ID (resolved): ${VORTEX_NOTIFY_NEWRELIC_APPID}"
note "Revision         : ${VORTEX_NOTIFY_NEWRELIC_REVISION}"
note "User             : ${VORTEX_NOTIFY_NEWRELIC_USER}"
note "Endpoint         : ${VORTEX_NOTIFY_NEWRELIC_ENDPOINT}"
note "Description      : ${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}"

task "Creating a deployment notification for application ${VORTEX_NOTIFY_NEWRELIC_APP_NAME} with ID ${VORTEX_NOTIFY_NEWRELIC_APPID}."
if ! curl -X POST "${VORTEX_NOTIFY_NEWRELIC_ENDPOINT}/applications/${VORTEX_NOTIFY_NEWRELIC_APPID}/deployments.json" \
  -L -s -o /dev/null -w "%{http_code}" \
  -H "Api-Key: ${VORTEX_NOTIFY_NEWRELIC_USER_KEY}" \
  -H 'Content-Type: application/json' \
  -d \
  "{
  \"deployment\": {
    \"revision\": \"${VORTEX_NOTIFY_NEWRELIC_REVISION}\",
    \"changelog\": \"${VORTEX_NOTIFY_NEWRELIC_CHANGELOG}\",
    \"description\": \"${VORTEX_NOTIFY_NEWRELIC_DESCRIPTION}\",
    \"user\": \"${VORTEX_NOTIFY_NEWRELIC_USER}\"
  }
}" | grep -q '201'; then
  fail "Failed to create a deployment notification for application ${VORTEX_NOTIFY_NEWRELIC_APP_NAME} with ID ${VORTEX_NOTIFY_NEWRELIC_APPID}"
  exit 1
fi

pass "Finished New Relic notification."
