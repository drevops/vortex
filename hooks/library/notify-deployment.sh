#!/usr/bin/env bash
##
# Acquia Cloud hook: Send deployment notifications.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"
branch="${3}"
ref="${4}:-${branch}"

# ODE environments do not support custom domains - use Acquia domains instead.
if [ "${target_env#ode}" != "${target_env}" ]; then
  url="https://${site}${target_env}.prod.acquia-sites.com"
else
  subdomain="${target_env}"

  # Re-map "test" to "stage".
  if [ "${subdomain#test}" != "${subdomain}" ]; then
    subdomain="stage"
  fi

  url="https://${subdomain}.${DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_ACQUIA_DOMAIN}"
fi

export DREVOPS_NOTIFY_PROJECT=$DREVOPS_PROJECT
export DREVOPS_NOTIFY_BRANCH=${branch}
export DREVOPS_NOTIFY_REF=${ref}
export DREVOPS_NOTIFY_SHA=${target_env}
export DREVOPS_NOTIFY_ENVIRONMENT_URL=$url
"/var/www/html/${site}.${target_env}/scripts/scripts/drevops/notify.sh"
