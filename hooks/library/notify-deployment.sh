#!/usr/bin/env bash
##
# Acquia Cloud hook: Notify Deployment.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

SITE="${1}"
TARGET_ENV="${2}"
BRANCH="${3}"

DOMAIN="your-site-url";

ACQUIA_DOMAIN="prod.acquia-sites.com";

# ------------------------------------------------------------------------------

[ -n "${SKIP_NOTIFY_DEPLOYMENT}" ] && echo "Skipping sending of deployment notification." && exit 0

export SCRIPTS_DIR="${SCRIPTS_DIR:-"/var/www/html/${SITE}.${TARGET_ENV}/scripts"}"

# ODE environments do not support custom domains - use acquia domains instead.
if [ "${TARGET_ENV#ode}" != "${TARGET_ENV}" ]; then
  url="https://${SITE}${TARGET_ENV}.${ACQUIA_DOMAIN}"
else
  subdomain="${TARGET_ENV}"

  # Re-map "test" to "stage".
  if [ "${subdomain#test}" != "${subdomain}" ]; then
    subdomain="stage"
  fi

  url="https://${subdomain}.${DOMAIN}";
fi

php "${SCRIPTS_DIR}/drevops/notify-deployment.php" \
  "YOURSITE" \
  "acquia-deploy@your-site-url" \
  "your.name@your-site-url|Your Name" \
  "${BRANCH}" \
  "${url}"
