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
# VORTEX_NOTIFY_REF="git-branch" \
# VORTEX_NOTIFY_ENVIRONMENT_URL="https://environment-url-example.com" \
# ./notify-email.sh
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Project name to notify.
VORTEX_NOTIFY_EMAIL_PROJECT="${VORTEX_NOTIFY_EMAIL_PROJECT:-${VORTEX_NOTIFY_PROJECT:-}}"

# Email address to send notifications from.
VORTEX_NOTIFY_EMAIL_FROM="${VORTEX_NOTIFY_EMAIL_FROM:-${DRUPAL_SITE_EMAIL:-}}"

# Email address(es) to send notifications to.
#
# Multiple names can be specified as a comma-separated list of email addresses
# with optional names in the format "email|name".
# Example: "to1@example.com|Jane Doe, to2@example.com|John Doe"
VORTEX_NOTIFY_EMAIL_RECIPIENTS="${VORTEX_NOTIFY_EMAIL_RECIPIENTS:-}"

# Git reference to notify about.
VORTEX_NOTIFY_EMAIL_REF="${VORTEX_NOTIFY_EMAIL_REF:-${VORTEX_NOTIFY_REF:-}}"

# Environment URL to notify about.
VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL="${VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL:-${VORTEX_NOTIFY_ENVIRONMENT_URL:-}}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

[ -z "${VORTEX_NOTIFY_EMAIL_PROJECT}" ] && fail "Missing required value for VORTEX_NOTIFY_EMAIL_PROJECT." && exit 1
[ -z "${VORTEX_NOTIFY_EMAIL_FROM}" ] && fail "Missing required value for VORTEX_NOTIFY_EMAIL_FROM." && exit 1
[ -z "${VORTEX_NOTIFY_EMAIL_RECIPIENTS}" ] && fail "Missing required value for VORTEX_NOTIFY_EMAIL_RECIPIENTS." && exit 1
[ -z "${VORTEX_NOTIFY_EMAIL_REF}" ] && fail "Missing required value for VORTEX_NOTIFY_EMAIL_REF." && exit 1
[ -z "${VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL}" ] && fail "Missing required value for VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL." && exit 1

info "Started email notification."

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

timestamp=$(date '+%d/%m/%Y %H:%M:%S %Z')
subject="${VORTEX_NOTIFY_EMAIL_PROJECT} deployment notification of \"${VORTEX_NOTIFY_EMAIL_REF}\""
content="## This is an automated message ##

Site ${VORTEX_NOTIFY_EMAIL_PROJECT} \"${VORTEX_NOTIFY_EMAIL_REF}\" branch has been deployed at ${timestamp} and is available at ${VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL}.

Login at: ${VORTEX_NOTIFY_EMAIL_ENVIRONMENT_URL}/user/login"

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
  name="${2#"${2%%[![:space:]]*}"}"
  name="${name%"${name##*[![:space:]]}"}"
  IFS="${old_ifs}"

  to="${name:+\"${name}\" }<${email}>"

  if [ "${has_sendmail}" = "1" ]; then
    (
      echo "To: ${to}"
      echo "Subject: ${subject}"
      echo "From: ${VORTEX_NOTIFY_EMAIL_FROM}"
      echo
      echo "${content}"
    ) | sendmail -t
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
else
  note "No notification emails were sent."
fi

pass "Finished email notification."
