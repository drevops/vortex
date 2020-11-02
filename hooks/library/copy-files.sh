#!/usr/bin/env bash
##
# Acquia Cloud hook: Copy files from production to the current environment.
#
set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

SITE="${1}"
TARGET_ENV="${2}"

[ -n "${SKIP_COPY_FILES}" ] && echo "Skipping copying of files Varnish." && exit 0

export SCRIPTS_DIR="${SCRIPTS_DIR:-"/var/www/html/${SITE}.${TARGET_ENV}/scripts"}"
export HOOKS_DIR="${HOOKS_DIR:-"/var/www/html/${SITE}.${TARGET_ENV}/hooks"}"

# Site name for AC API.
export AC_API_APP_NAME="${AC_API_APP_NAME:-${SITE}}"

# Acquia FILES deploy details.
export AC_API_FILES_SRC_ENV="${AC_API_FILES_SRC_ENV:-prod}"
export AC_API_FILES_DST_ENV="${AC_API_FILES_DST_ENV:-${TARGET_ENV}}"

"$SCRIPTS_DIR/drevops/copy-db-acquia.sh"
