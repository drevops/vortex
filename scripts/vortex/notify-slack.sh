#!/usr/bin/env bash
##
# Notification dispatch to Slack.
#
# Sends deployment notifications to Slack channels using Incoming Webhooks.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Slack notification project name.
VORTEX_NOTIFY_SLACK_PROJECT="${VORTEX_NOTIFY_SLACK_PROJECT:-${VORTEX_NOTIFY_PROJECT:-}}"

# Slack notification git branch name.
VORTEX_NOTIFY_SLACK_BRANCH="${VORTEX_NOTIFY_SLACK_BRANCH:-${VORTEX_NOTIFY_BRANCH:-}}"

# Slack notification git commit SHA.
VORTEX_NOTIFY_SLACK_SHA="${VORTEX_NOTIFY_SLACK_SHA:-${VORTEX_NOTIFY_SHA:-}}"

# Slack notification pull request number.
VORTEX_NOTIFY_SLACK_PR_NUMBER="${VORTEX_NOTIFY_SLACK_PR_NUMBER:-${VORTEX_NOTIFY_PR_NUMBER:-}}"

# Slack notification deployment environment URL.
VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL="${VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL:-${VORTEX_NOTIFY_ENVIRONMENT_URL:-}}"

# Slack notification event type. Can be 'pre_deployment' or 'post_deployment'.
VORTEX_NOTIFY_SLACK_EVENT="${VORTEX_NOTIFY_SLACK_EVENT:-${VORTEX_NOTIFY_EVENT:-post_deployment}}"

# Slack notification webhook URL.
# The incoming Webhook URL from your Slack app configuration.
# @see https://www.vortextemplate.com/docs/workflows/notifications#slack
VORTEX_NOTIFY_SLACK_WEBHOOK="${VORTEX_NOTIFY_SLACK_WEBHOOK:-}"

# Slack notification target channel (optional, overrides webhook default).
# Format: #channel-name or @username
VORTEX_NOTIFY_SLACK_CHANNEL="${VORTEX_NOTIFY_SLACK_CHANNEL:-}"

# Slack notification bot display name (optional).
VORTEX_NOTIFY_SLACK_USERNAME="${VORTEX_NOTIFY_SLACK_USERNAME:-Deployment Bot}"

# Slack notification bot icon emoji (optional).
VORTEX_NOTIFY_SLACK_ICON_EMOJI="${VORTEX_NOTIFY_SLACK_ICON_EMOJI:-:rocket:}"

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

[ -z "${VORTEX_NOTIFY_SLACK_PROJECT}" ] && fail "Missing required value for VORTEX_NOTIFY_SLACK_PROJECT" && exit 1
[ -z "${VORTEX_NOTIFY_SLACK_BRANCH}" ] && fail "Missing required value for VORTEX_NOTIFY_SLACK_BRANCH" && exit 1
[ -z "${VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL}" ] && fail "Missing required value for VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL" && exit 1
[ -z "${VORTEX_NOTIFY_SLACK_WEBHOOK}" ] && fail "Missing required value for VORTEX_NOTIFY_SLACK_WEBHOOK" && exit 1

info "Started Slack notification."

# Determine reference type and format for display.
ref_info="\"${VORTEX_NOTIFY_SLACK_BRANCH}\" branch"
if [ -n "${VORTEX_NOTIFY_SLACK_PR_NUMBER}" ]; then
  ref_info="\"PR-${VORTEX_NOTIFY_SLACK_PR_NUMBER}\""
fi

# Generate timestamp.
timestamp=$(date '+%d/%m/%Y %H:%M:%S %Z')

# Determine color based on event type.
color="good"
event_label="Deployment Complete"
if [ "${VORTEX_NOTIFY_SLACK_EVENT}" = "pre_deployment" ]; then
  color="#808080"
  event_label="Deployment Starting"
fi

# Build the message title.
title="${event_label}: ${VORTEX_NOTIFY_SLACK_PROJECT}"

# Truncate SHA to 8 characters if available.
short_sha=""
if [ -n "${VORTEX_NOTIFY_SLACK_SHA}" ]; then
  short_sha="${VORTEX_NOTIFY_SLACK_SHA:0:8}"
fi

# Build payload using PHP to properly escape JSON and avoid brace parsing issues.
payload=$(php -r "
\$data = array(
  'username' => '${VORTEX_NOTIFY_SLACK_USERNAME}',
  'icon_emoji' => '${VORTEX_NOTIFY_SLACK_ICON_EMOJI}',
  'attachments' => array(
    array(
      'color' => '${color}',
      'fallback' => '${event_label}: ${VORTEX_NOTIFY_SLACK_PROJECT} - ${VORTEX_NOTIFY_SLACK_BRANCH}',
      'title' => '${title}',
      'fields' => array(
        array('title' => 'Branch', 'value' => '${VORTEX_NOTIFY_SLACK_BRANCH}', 'short' => true)
      ),
      'footer' => 'Vortex Deployment',
      'ts' => time()
    )
  )
);

if (!empty('${VORTEX_NOTIFY_SLACK_PR_NUMBER}')) {
  \$data['attachments'][0]['fields'][] = array('title' => 'PR Number', 'value' => '#${VORTEX_NOTIFY_SLACK_PR_NUMBER}', 'short' => true);
}

if (!empty('${short_sha}')) {
  \$data['attachments'][0]['fields'][] = array('title' => 'Commit', 'value' => '${short_sha}', 'short' => true);
}

\$data['attachments'][0]['fields'][] = array('title' => 'Environment', 'value' => '<${VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL}|View Site>', 'short' => true);
\$data['attachments'][0]['fields'][] = array('title' => 'Time', 'value' => '${timestamp}', 'short' => true);

if (!empty('${VORTEX_NOTIFY_SLACK_CHANNEL}')) {
  \$data['channel'] = '${VORTEX_NOTIFY_SLACK_CHANNEL}';
}

echo json_encode(\$data, JSON_UNESCAPED_SLASHES);
")

# Send notification to Slack.
response=$(curl -s -o /dev/null -w "%{http_code}" \
  -X POST \
  -H "Content-Type: application/json" \
  -d "${payload}" \
  "${VORTEX_NOTIFY_SLACK_WEBHOOK}")

if [ "${response}" != "200" ]; then
  fail "Unable to send notification to Slack. HTTP status: ${response}"
  exit 1
fi

note "Notification sent to Slack."
note "Project: ${VORTEX_NOTIFY_SLACK_PROJECT}"
note "Reference: ${ref_info}"
note "Environment: ${VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL}"

pass "Finished Slack notification."
