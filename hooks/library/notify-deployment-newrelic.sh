#!/usr/bin/env bash
##
# Acquia Cloud hook: Notify Newrelic.
#
# shellcheck disable=SC1090

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

SITE="${1}"
TARGET_ENV="${2}"
BRANCH="${3}"
REF="${4}:-${BRANCH}"

# Flag to enable New Relic.
NEWRELIC_ENABLED="${NEWRELIC_ENABLED:-}"

# New Relic license.
NEWRELIC_LICENSE="${NEWRELIC_LICENSE:-}"

NOTIFY_NEWRELIC_APIKEY="${NOTIFY_NEWRELIC_APIKEY:-}"

# ------------------------------------------------------------------------------

[ -n "${SKIP_NOTIFY_DEPLOYMENT}" ] && echo "Skipping sending of deployment notification." && exit 0

export SCRIPTS_DIR="${SCRIPTS_DIR:-"/var/www/html/${SITE}.${TARGET_ENV}/scripts"}"

if [ "${NEWRELIC_ENABLED}" == "1" ] && [ -n "${NEWRELIC_LICENSE}" ] && [ -n "${NOTIFY_NEWRELIC_APIKEY}" ]; then
  NOTIFY_NEWRELIC_APPNAME="${SITE}-${TARGET_ENV}" \
  NOTIFY_DEPLOY_REF="${REF}" \
  . "${SCRIPTS_DIR}"/drevops/notify-deployment-newrelic.sh
fi
