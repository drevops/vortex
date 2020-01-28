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
  current_env="${TARGET_ENV}"
  # Strip placeholder for PROD environment.
  if [ "${current_env}" == "prod" ] ; then
    domain="${domain//\$this_env./}"
  fi

  # Re-map 'test' to 'stg'.
  if [ "${current_env}" == "test" ] ; then
    current_env=stage
  fi

  # Disable replacement for unknown environments.
  if [ "${current_env}" != "dev" ] && [ "${current_env}" != "stage" ] ; then
    current_env=""
  fi

  # Proceed only if the environment was provided.
  if [ "${current_env}" != "" ] ; then
    # Interpolate variables in domain name.
    domain="$(eval echo "${domain}")"

    # Clear varnish cache.
    # shellcheck disable=SC2086
    echo drush @${SITE}.${current_env} ac-domain-purge "${domain}"
    # shellcheck disable=SC2086
    drush @${SITE}.${current_env} ac-domain-purge "${domain}" || true
  fi
done < "${DOMAINS_FILE}"
