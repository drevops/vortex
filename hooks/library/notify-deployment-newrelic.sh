#!/usr/bin/env bash
##
# Acquia Cloud hook: Send deployment notification to New Relic.
#
# Environment variables must be set in Acquia UI globally or for each environment.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"
branch="${3}"
ref="${4}:-${branch}"

[ "${DREVOPS_TASK_NOTIFY_DEPLOYMENT_NEWRELIC_ACQUIA_SKIP}" = "1" ] && echo "Skip New Relic deployment notification in Acquia environment." && exit 0

if [ "${NEWRELIC_ENABLED}" = "1" ] && [ -n "${NEWRELIC_LICENSE}" ] && [ -n "${DREVOPS_NOTIFY_NEWRELIC_APIKEY}" ]; then
  export DREVOPS_NOTIFY_NEWRELIC_APP_NAME="${site}-${target_env}"
  export DREVOPS_NOTIFY_DEPLOY_REF="${ref}"
  "/var/www/html/${site}.${target_env}/scripts/drevops/notify-deployment-newrelic.sh"
fi
