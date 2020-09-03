#!/usr/bin/env bash
# shellcheck disable=SC2086,SC2002
##
# Install site from canonical database.
#
# shellcheck disable=SC2235

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
APP="${APP:-/app}"

# Path to the DOCROOT.
WEBROOT="${WEBROOT:-docroot}"

# Drush alias.
DRUSH_ALIAS="${DRUSH_ALIAS:-}"

# Drupal custom module prefix.
# If provided, the ${DRUPAL_MODULE_PREFIX}_core will be enabled (if exists).
DRUPAL_MODULE_PREFIX="${DRUPAL_MODULE_PREFIX:-}"

# Drupal site name
DRUPAL_SITE_NAME="${DRUPAL_SITE_NAME:-Example site}"

# Profile machine name.
DRUPAL_PROFILE="${DRUPAL_PROFILE:-standard}"

# Path to configuration directory.
DRUPAL_CONFIG_PATH="${DRUPAL_CONFIG_PATH:-${APP}/config/default}"

# Config label.
DRUPAL_CONFIG_LABEL="${DRUPAL_CONFIG_LABEL:-}"

# Path to private files.
DRUPAL_PRIVATE_FILES="${DRUPAL_PRIVATE_FILES:-${APP}/${WEBROOT}/sites/default/files/private}"

# Flag to unblock admin.
DRUPAL_UNBLOCK_ADMIN="${DRUPAL_UNBLOCK_ADMIN:-1}"

# Directory with database dump file.
DB_DIR="${DB_DIR:-./.data}"

# Database dump file name.
DB_FILE="${DB_FILE:-db.sql}"

# Flag to export database before import.
DB_EXPORT_BEFORE_IMPORT="${DB_EXPORT_BEFORE_IMPORT:-0}"

# Flag to skip DB import.
SKIP_DB_IMPORT="${SKIP_DB_IMPORT:-}"

# Flag to skip running post DB import commands.
SKIP_POST_DB_IMPORT="${SKIP_POST_DB_IMPORT:-}"

# Flag to force fresh install even if the site exists.
FORCE_FRESH_INSTALL="${FORCE_FRESH_INSTALL:-}"

# Flag to always overwrite existing database. Usually set to 0 in deployed
# environments.
DB_IMPORT_OVERWRITE_EXISTING="${DB_IMPORT_OVERWRITE_EXISTING:-1}"

# ------------------------------------------------------------------------------

echo "==> Installing site."

drush="${APP}/vendor/bin/drush"

# Use local or global Drush.
drush="$(command -v drush || echo "${APP}/vendor/bin/drush")"

# Create private files directory.
mkdir -p "${DRUPAL_PRIVATE_FILES}"

# Export database before importing, if the flag is set.
# Useful to automatically store database dump before starting site rebuild.
[ "${DB_EXPORT_BEFORE_IMPORT}" -eq 1 ] && ./scripts/drevops/drupal-export-db.sh

site_is_installed="$($drush ${DRUSH_ALIAS} status --fields=bootstrap | grep -q "Successful" && echo true || echo false)"

# Import database dump if present, or install fresh website from the profile if
# site is not already installed.
# Note that if the site will be re-installed from the database only if it does
# not already exist.
if [ -z "${SKIP_DB_IMPORT}" ] && [ -f "${DB_DIR}/${DB_FILE}" ] && ( [ "${site_is_installed}" != "true" ] || [ "${DB_IMPORT_OVERWRITE_EXISTING}" ==  "1" ] ) ; then
  echo "==> Using existing DB dump ${DB_DIR}/${DB_FILE}."
  DB_DIR="${DB_DIR}" DB_FILE="${DB_FILE}" ./scripts/drevops/drupal-import-db.sh
elif [ "${site_is_installed}" == "true" ] && [ "${FORCE_FRESH_INSTALL}" != "1" ]; then
  echo "==> Existing site found. Re-run with FORCE_FRESH_INSTALL=1 to forcefully re-install."
else
  echo "==> Existing site not found. Installing site from profile ${DRUPAL_PROFILE}."

  if ls "${DRUPAL_CONFIG_PATH}"/*.yml > /dev/null 2>&1; then
    $drush ${DRUSH_ALIAS} si "${DRUPAL_PROFILE}" -y --account-name=admin --site-name="${DRUPAL_SITE_NAME}" --config-dir=${DRUPAL_CONFIG_PATH} install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL
    $drush ${DRUSH_ALIAS} updb -y
    SKIP_POST_DB_IMPORT=1
  else
    $drush ${DRUSH_ALIAS} si "${DRUPAL_PROFILE}" -y --account-name=admin --site-name="${DRUPAL_SITE_NAME}" install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL
  fi
fi

# Skip running of post DB import scripts and finish installation.
if [ -n "${SKIP_POST_DB_IMPORT}" ]; then
  echo "==> Skipped running of post DB init commands."
  # Rebuild cache.
  $drush ${DRUSH_ALIAS} cr
  # Exit as there is nothing that should be ran after this.
  exit 0
fi

echo "==> Running post DB init commands."

# Run updates.
$drush ${DRUSH_ALIAS} updb -y

# Import Drupal configuration, if configuration files exist.
if ls "${DRUPAL_CONFIG_PATH}"/*.yml > /dev/null 2>&1; then
  if [ -f "${DRUPAL_CONFIG_PATH}/system.site.yml" ]; then
    config_uuid="$(cat "${DRUPAL_CONFIG_PATH}/system.site.yml" | grep uuid | tail -c +7 | head -c 36)"
    $drush config-set system.site uuid "${config_uuid}"
  fi

  $drush ${DRUSH_ALIAS} cim "${DRUPAL_CONFIG_LABEL}" -y

  if $drush pml --status=enabled | grep -q config_split; then
    $drush ${DRUSH_ALIAS} config-split:import -y
  fi
else
  echo "==> Configuration was not found in ${DRUPAL_CONFIG_PATH} path."
fi

# Rebuild cache.
$drush ${DRUSH_ALIAS} cr

# Run post-config import updates for the cases when updates rely on imported configuration.
$drush ${DRUSH_ALIAS} post-config-import-update

# Unblock admin user.
if [ "${DRUPAL_UNBLOCK_ADMIN}" == "1" ]; then
  $drush ${DRUSH_ALIAS} sqlq "SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" | head -n 1 | xargs $drush -- uublk
  $drush uli
fi
