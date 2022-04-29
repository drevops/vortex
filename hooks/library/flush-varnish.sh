#!/usr/bin/env bash
##
# Acquia Cloud hook: Flush Varnish cache for specified domains.
#
set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

SITE="${1}"
TARGET_ENV="${2}"

[ "${SKIP_FLUSH_VARNISH}" = "1" ] && echo "Skipping flush Varnish." && exit 0

export SCRIPTS_DIR="${SCRIPTS_DIR:-"/var/www/html/${SITE}.${TARGET_ENV}/scripts"}"
export HOOKS_DIR="${HOOKS_DIR:-"/var/www/html/${SITE}.${TARGET_ENV}/hooks"}"

# Site name for AC API.
export AC_API_APP_NAME="${AC_API_APP_NAME:-${SITE}}"

export AC_API_VARNISH_ENV="${TARGET_ENV}"
export AC_API_VARNISH_DOMAINS_FILE="${HOOKS_DIR}/library/domains.txt"

"$SCRIPTS_DIR/drevops/purge-cache-acquia.sh"
