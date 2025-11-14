#!/usr/bin/env bash
##
# Notification dispatch to email recipients.
#
# Notification dispatch to email recipients.
#
# Usage:
# VORTEX_NOTIFY_PROJECT="Site Name" \
# DRUPAL_SITE_EMAIL="from@example.com" \
# VORTEX_NOTIFY_EMAIL_RECIPIENTS="to1@example.com|Jane Doe, to2@example.com|John Doe" \
# VORTEX_NOTIFY_LABEL="main" \
# VORTEX_NOTIFY_ENVIRONMENT_URL="https://environment-url-example.com" \
# ./notify-email.sh
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Email notification project name.
VORTEX_NOTIFY_EMAIL_PROJECT="${VORTEX_NOTIFY_EMAIL_PROJECT:-${VORTEX_NOTIFY_PROJECT:-}}"

# Email notification sender address.
VORTEX_NOTIFY_EMAIL_FROM="${VORTEX_NOTIFY_EMAIL_FROM:-${DRUPAL_SITE_EMAIL:-}}"

# Email notification recipients.
#
# Multiple names can be specified as a comma-separated list of email addresses
# with optional names in the format "email|name".
# Example: "to1@example.com|Jane Doe, to2@example.com|John Doe"
VORTEX_NOTIFY_EMAIL_RECIPIENTS="${VORTEX_NOTIFY_EMAIL_RECIPIENTS:-}"

# Email notification deployment label (branch name, PR number, or custom identifier).
VORTEX_NOTIFY_EMAIL_LABEL="${VORTEX_NOTIFY_EMAIL_LABEL:-${VORTEX_NOTIFY_LABEL:-}}"

# Email notification environment URL.
VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL="${VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL:-${VORTEX_NOTIFY_ENVIRONMENT_URL:-}}"

# Email notification login URL.
VORTEX_NOTIFY_EMAIL_LOGIN_URL="${VORTEX_NOTIFY_EMAIL_LOGIN_URL:-${VORTEX_NOTIFY_LOGIN_URL:-}}"

# Email notification message template.
# Available tokens: %project%, %label%, %timestamp%, %environment_url%, %login_url%
VORTEX_NOTIFY_EMAIL_MESSAGE="${VORTEX_NOTIFY_EMAIL_MESSAGE:-}"

# Email notification event type. Can be 'pre_deployment' or 'post_deployment'.
VORTEX_NOTIFY_EMAIL_EVENT="${VORTEX_NOTIFY_EMAIL_EVENT:-${VORTEX_NOTIFY_EVENT:-post_deployment}}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

[ -z "${VORTEX_NOTIFY_EMAIL_PROJECT}" ] && fail "Missing required value for VORTEX_NOTIFY_EMAIL_PROJECT." && exit 1
[ -z "${VORTEX_NOTIFY_EMAIL_FROM}" ] && fail "Missing required value for VORTEX_NOTIFY_EMAIL_FROM." && exit 1
[ -z "${VORTEX_NOTIFY_EMAIL_RECIPIENTS}" ] && fail "Missing required value for VORTEX_NOTIFY_EMAIL_RECIPIENTS." && exit 1
[ -z "${VORTEX_NOTIFY_EMAIL_LABEL}" ] && fail "Missing required value for VORTEX_NOTIFY_EMAIL_LABEL." && exit 1
[ -z "${VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL}" ] && fail "Missing required value for VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL." && exit 1
[ -z "${VORTEX_NOTIFY_EMAIL_LOGIN_URL}" ] && fail "Missing required value for VORTEX_NOTIFY_EMAIL_LOGIN_URL." && exit 1

info "Started email notification."

# Set default message template if not provided.
if [ -z "${VORTEX_NOTIFY_EMAIL_MESSAGE}" ]; then
  VORTEX_NOTIFY_EMAIL_MESSAGE="## This is an automated message ##

Site %project% %label% has been deployed at %timestamp% and is available at %environment_url%.

Login at: %login_url%"
fi

# Skip if this is a pre-deployment event (email only for post-deployment).
if [ "${VORTEX_NOTIFY_EMAIL_EVENT}" = "pre_deployment" ]; then
  pass "Skipping email notification for pre_deployment event."
  exit 0
fi

has_sendmail=0
has_mail=0
if command -v sendmail >/dev/null 2>&1; then
  note "Using sendmail command to send emails."
  has_sendmail=1
elif command -v mail >/dev/null 2>&1; then
  note "Using mail command to send emails."
  has_mail=1
else
  fail "Neither mail nor sendmail commands are available."
  exit 1
fi

# Build message by replacing tokens.
timestamp=$(date '+%d/%m/%Y %H:%M:%S %Z')
subject="${VORTEX_NOTIFY_EMAIL_PROJECT} deployment notification of ${VORTEX_NOTIFY_EMAIL_LABEL}"

# Replace tokens in message template.
content="${VORTEX_NOTIFY_EMAIL_MESSAGE}"
content=$(REPLACEMENT="${VORTEX_NOTIFY_EMAIL_PROJECT}" TEMPLATE="${content}" php -r 'echo str_replace("%project%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
content=$(REPLACEMENT="${VORTEX_NOTIFY_EMAIL_LABEL}" TEMPLATE="${content}" php -r 'echo str_replace("%label%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
content=$(REPLACEMENT="${timestamp}" TEMPLATE="${content}" php -r 'echo str_replace("%timestamp%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
content=$(REPLACEMENT="${VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL}" TEMPLATE="${content}" php -r 'echo str_replace("%environment_url%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
content=$(REPLACEMENT="${VORTEX_NOTIFY_EMAIL_LOGIN_URL}" TEMPLATE="${content}" php -r 'echo str_replace("%login_url%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')

info "Email notification summary:"
note "Project        : ${VORTEX_NOTIFY_EMAIL_PROJECT}"
note "Deployment     : ${VORTEX_NOTIFY_EMAIL_LABEL}"
note "Environment URL: ${VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL}"
note "Login URL      : ${VORTEX_NOTIFY_EMAIL_LOGIN_URL}"
note "From           : ${VORTEX_NOTIFY_EMAIL_FROM}"
note "Recipients     : ${VORTEX_NOTIFY_EMAIL_RECIPIENTS}"
note "Subject        : ${subject}"
note "Content        : ${content}"

sent=""
IFS=","
# shellcheck disable=SC2086
set -- ${VORTEX_NOTIFY_EMAIL_RECIPIENTS}
for email_with_name; do
  old_ifs="${IFS}"
  IFS="|"
  # shellcheck disable=SC2086
  set -- ${email_with_name}

  email="${1#"${1%%[![:space:]]*}"}"
  email="${email%"${email##*[![:space:]]}"}"

  if [ $# -gt 1 ]; then
    name="${2#"${2%%[![:space:]]*}"}"
    name="${name%"${name##*[![:space:]]}"}"
  else
    name=""
  fi

  IFS="${old_ifs}"

  if [ -n "${name}" ]; then
    to="${name:+\"${name}\" }<${email}>"
  else
    to="${email}"
  fi

  if [ "${has_sendmail}" = "1" ]; then
    (
      echo "To: ${to}"
      echo "Subject: ${subject}"
      echo "From: ${VORTEX_NOTIFY_EMAIL_FROM}"
      echo
      echo "${content}"
    ) | sendmail -t -f "${VORTEX_NOTIFY_EMAIL_FROM}"
    sent="${sent} ${email}"
  elif [ "${has_mail}" = "1" ]; then
    mail -s "${subject}" "${to}" <<-EOF
    From: ${VORTEX_NOTIFY_EMAIL_FROM}

    ${content}
EOF
    sent="${sent} ${email}"
  fi
done

sent="${sent#"${sent%%[![:space:]]*}"}"
sent="${sent%"${sent##*[![:space:]]}"}"

if [ -n "${sent}" ]; then
  note "Notification email(s) sent to: ${sent// /, }"
  note "Subject: ${subject}"
  note "Content: ${content}"
else
  note "No notification emails were sent."
fi

pass "Finished email notification."
