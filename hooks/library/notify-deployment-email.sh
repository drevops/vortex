#!/usr/bin/env bash
##
# Acquia Cloud hook: Send deployment notification to email.
#
# Environment variables must be set in Acquia UI globally or for each environment.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"
branch="${3}"

[ "${DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_ACQUIA_SKIP}" = "1" ] && echo "Skip email deployment notification in Acquia environment." && exit 0

DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_ACQUIA_SITE_NAME="${DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_ACQUIA_SITE_NAME:-not set}"
DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_ACQUIA_DOMAIN="${DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_ACQUIA_DOMAIN:-not set}"
DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_FROM="${DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_FROM:-not set}"
# Set as a comma-separated list of "your.name@example.com|Your Name".
DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_TO="${DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_TO:-not set}"

# ODE environments do not support custom domains - use cquia domains instead.
if [ "${target_env#ode}" != "${target_env}" ]; then
  url="https://${site}${target_env}.prod.acquia-sites.com"
else
  subdomain="${target_env}"

  # Re-map "test" to "stage".
  if [ "${subdomain#test}" != "${subdomain}" ]; then
    subdomain="stage"
  fi

  url="https://${subdomain}.${DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_ACQUIA_DOMAIN}";
fi

php "/var/www/html/${site}.${target_env}/scripts/drevops/notify-deployment-email.php" \
  "${DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_ACQUIA_SITE_NAME}" \
  "${DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_FROM}" \
  "${DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_TO}" \
  "${branch}" \
  "${url}"
