#!/usr/bin/env bash
##
# Acquia Cloud hook: Flush varnish cache for specified domains.
#
# Support sub-domains and custom domains.
# Place your domains into domains.txt file.
#
# IMPORTANT! This script uses drush ac-* commands and requires credentials
# for Acquia Cloud. Make sure that file "${HOME}/.acquia/cloudapi.conf" exists
# and follow deployment instructions if it does not.

set -e
set -x

SITE="${1}"
TARGET_ENV="${2}"
APP="/var/www/html/${SITE}.${TARGET_ENV}"
DOMAINS_FILE="${DOMAINS_FILE:-${APP}/hooks/library/domains.txt}"

# ------------------------------------------------------------------------------

[ ! -f "${DOMAINS_FILE}" ] && echo "ERROR: File with domains does not exist." && exit 1
[ ! -f "${HOME}/.acquia/cloudapi.conf" ] && echo "ERROR: Acquia Cloud API credentials file ${HOME}/.acquia/cloudapi.conf does not exist." && exit 1

while read -r domain; do
  # Special variable to remap target env to the sub-domain prefix based on UI name.
  TARGET_ENV_REMAP="${TARGET_ENV}"
  # Strip placeholder for PROD environment.
  if [ "${TARGET_ENV}" == "prod" ] ; then
    domain="${domain//\$TARGET_ENV_REMAP./}"
    domain="${domain//\$TARGET_ENV./}"
  fi

  # Re-map 'test' to 'stage' as seen in UI.
  if [ "${TARGET_ENV}" == "test" ] ; then
    TARGET_ENV_REMAP=stage
  fi

  # Disable replacement for unknown environments.
  if [ "${TARGET_ENV}" != "dev" ] && [ "${TARGET_ENV}" != "test" ] && [ "${TARGET_ENV}" != "prod" ]; then
    TARGET_ENV_REMAP=""
  fi

  # Proceed only if the environment was provided.
  if [ "${TARGET_ENV_REMAP}" != "" ] ; then
    # Interpolate variables in domain name.
    domain="$(eval echo "${domain}")"

    # Clear varnish cache.
    # shellcheck disable=SC2086
    echo drush @${SITE}.${TARGET_ENV} ac-domain-purge "${domain}" || true
  fi
done < "${DOMAINS_FILE}"
