#!/usr/bin/env bash
##
# Acquia Cloud hook: Install site.
#

set -e
set -x

SITE="${1}"
TARGET_ENV="${2}"

[ -n "${SKIP_INSTALL_SITE}" ] && echo "Skipping install site." && exit

# Create drush alias from arguments.
export DRUSH_ALIAS="@${SITE}.${TARGET_ENV}"

# Skip DB import as it is managed through UI.
export SKIP_DB_IMPORT=1

export APP="/var/www/html/${SITE}.${TARGET_ENV}"
export SCRIPTS_DIR="${APP}/scripts"

"$SCRIPTS_DIR"/drevops/drupal-install-site.sh
