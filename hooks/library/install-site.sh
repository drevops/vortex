#!/usr/bin/env bash
##
# Acquia Cloud hook: Install site.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

SITE="${1}"
TARGET_ENV="${2}"

[ -n "${SKIP_INSTALL_SITE}" ] && echo "Skipping install site." && exit

export APP="/var/www/html/${SITE}.${TARGET_ENV}"
export SCRIPTS_DIR="${SCRIPTS_DIR:-"${APP}/scripts"}"

# Create drush alias from arguments.
export DRUSH_ALIAS="@${SITE}.${TARGET_ENV}"

# Override config label.
export DRUPAL_CONFIG_LABEL=vcs

# Skip DB import as it is managed through UI.
export SKIP_DB_IMPORT=1

# Do not sanitize DB.
export SKIP_DB_SANITIZE=1

# Do not unblock admin account.
export DRUPAL_UNBLOCK_ADMIN=0

"$SCRIPTS_DIR"/drevops/drupal-install-site.sh
