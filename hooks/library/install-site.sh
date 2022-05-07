#!/usr/bin/env bash
##
# Acquia Cloud hook: Install site.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"

[ "${DREVOPS_TASK_DRUPAL_SITE_INSTALL_ACQUIA_SKIP}" = "1" ] && echo "Skipping install site." && exit

export DREVOPS_APP="/var/www/html/${site}.${target_env}"

# Override Drupal config label.
export DREVOPS_DRUPAL_CONFIG_LABEL=vcs

# Skip DB import as it is managed through UI.
export DREVOPS_DRUPAL_SKIP_DB_IMPORT="${DREVOPS_DRUPAL_SKIP_DB_IMPORTL:-1}"

# Do not sanitize DB.
export DREVOPS_DRUPAL_DB_SANITIZE_SKIP="${DREVOPS_DRUPAL_DB_SANITIZE_SKIP:-1}"

# Do not unblock admin account.
export DREVOPS_DRUPAL_UNBLOCK_ADMIN="${DREVOPS_DRUPAL_UNBLOCK_ADMIN:-0}"

"/var/www/html/${site}.${target_env}/drevops/drupal-install-site.sh"
