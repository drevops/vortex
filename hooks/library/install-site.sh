#!/usr/bin/env bash
##
# Acquia Cloud hook: Install site.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

SITE="${1}"
TARGET_ENV="${2}"

[ "${SKIP_INSTALL_SITE}" = "1" ] && echo "Skipping install site." && exit

export DREVOPS_APP="/var/www/html/${SITE}.${TARGET_ENV}"
export SCRIPTS_DIR="${SCRIPTS_DIR:-"${DREVOPS_APP}/scripts"}"

# Create drush alias from arguments.
export DREVOPS_DRUSH_ALIAS="@${SITE}.${TARGET_ENV}"

# Override config label.
export DREVOPS_DRUPAL_CONFIG_LABEL=vcs

# Skip DB import as it is managed through UI.
export DREVOPS_DRUPAL_SKIP_DB_IMPORT=1

# Do not sanitize DB.
export DREVOPS_DRUPAL_DB_SANITIZE_SKIP=1

# Do not unblock admin account.
export DREVOPS_DRUPAL_UNBLOCK_ADMIN=0

"$SCRIPTS_DIR"/drevops/drupal-install-site.sh
