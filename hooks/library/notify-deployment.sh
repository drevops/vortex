#!/usr/bin/env bash
##
# Acquia Cloud hook: Send deployment notifications.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"
branch="${3}"
ref="${4:-${branch}}"

# Custom domain name for the environment.
DREVOPS_NOTIFY_ENVIRONMENT_DOMAIN="${DREVOPS_NOTIFY_ENVIRONMENT_DOMAIN:-}"

export DREVOPS_APP="/var/www/html/${site}.${target_env}"

pushd "${DREVOPS_APP}" >/dev/null || exit 1

# Set URL to Acquia domain by default.
url="https://${AH_SITE_NAME}.${AH_REALM:-prod}.acquia-sites.com"

# Use custom domain for non-ODE environments, if provided.
if [ -n "${DREVOPS_NOTIFY_ENVIRONMENT_DOMAIN}" ] && [ "${target_env#ode}" = "${target_env}" ]; then
  subdomain="${target_env}"
  [ "${subdomain}" = "test" ] && subdomain="stage"
  url="https://${subdomain}.${DREVOPS_NOTIFY_ENVIRONMENT_DOMAIN}"
fi

export DREVOPS_NOTIFY_PROJECT="${site}"
export DREVOPS_NOTIFY_BRANCH="${branch}"
export DREVOPS_NOTIFY_REF="${ref}"
export DREVOPS_NOTIFY_SHA="${target_env}"
export DREVOPS_NOTIFY_ENVIRONMENT_URL="${url}"

./scripts/drevops/notify.sh

popd >/dev/null || exit 1
