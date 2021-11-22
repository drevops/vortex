#!/usr/bin/env bash
##
# Install site from database or profile, run updates and import configuration.
#
# shellcheck disable=SC2086,SC2002,SC2235,SC1090

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
DB_DIR="${DB_DIR:-${APP}/.data}"

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

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${APP}/vendor/bin/drush" ]; then echo "${APP}/vendor/bin/drush"; else command -v drush; fi)"

# Create private files directory.
mkdir -p "${DRUPAL_PRIVATE_FILES}"

# Export database before importing, if the flag is set.
# Useful to automatically store database dump before starting site rebuild.
[ "${DB_EXPORT_BEFORE_IMPORT}" -eq 1 ] && "${APP}/scripts/drevops/drupal-export-db.sh"

site_is_installed="$($drush ${DRUSH_ALIAS} status --fields=bootstrap | grep -q "Successful" && echo "1" || echo "0")"

# Install site from the database dump or from profile.
if
  # Not skipping DB import AND
  [ -z "${SKIP_DB_IMPORT}" ] &&
  # DB dump file exists AND
  [ -f "${DB_DIR}/${DB_FILE}" ] &&
  # Site is not installed OR allowed to overwrite existing site.
  ([ "${site_is_installed}" != "1" ] || [ "${DB_IMPORT_OVERWRITE_EXISTING}" == "1" ])
then
  echo "==> Using existing DB dump ${DB_DIR}/${DB_FILE}."
  DB_DIR="${DB_DIR}" DB_FILE="${DB_FILE}" "${APP}/scripts/drevops/drupal-import-db.sh"
elif
  # If site is installed AND
  [ "${site_is_installed}" == "1" ] &&
  # Not allowed to forcefully install from profile.
  [ "${FORCE_FRESH_INSTALL}" != "1" ]
then
  echo "==> Existing site found. Re-run with FORCE_FRESH_INSTALL=1 to forcefully re-install."
else
  echo "==> Existing site not found. Installing site from profile ${DRUPAL_PROFILE}."
  # Install from profile with default configuration.
  $drush ${DRUSH_ALIAS} si "${DRUPAL_PROFILE}" -y --account-name=admin --site-name="${DRUPAL_SITE_NAME}" install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL
fi

# Skip running of post DB import scripts and finish installation.
# Useful when need to capture database state before any updates ran (for
# example, DB caching in CI).
if [ -n "${SKIP_POST_DB_IMPORT}" ]; then
  echo "==> Skipped running of post DB init commands."
  # Rebuild cache.
  $drush ${DRUSH_ALIAS} cc all
  # Sanitize DB.
  "${APP}/scripts/drevops/drupal-sanitize-db.sh"
  # Exit as there is nothing that should be ran after this.
  exit 0
fi

echo "==> Running post DB init commands."

echo -n "==> Current Drupal environment: "
environment="$($drush ${DRUSH_ALIAS} ev "global \$conf; print \$conf['environment'];")"
echo "${environment}" && echo

if [ -n "${DRUPAL_BUILD_WITH_MAINTENANCE_MODE}" ]; then
  echo "==> Enabled maintenance mode."
  $drush ${DRUSH_ALIAS} vset maintenance_mode 1 --format=boolean
fi

# Run updates.
$drush ${DRUSH_ALIAS} updb -y

# Rebuild cache.
$drush ${DRUSH_ALIAS} cc all

# Sanitize database.
"${APP}/scripts/drevops/drupal-sanitize-db.sh"

# Unblock admin user.
if [ "${DRUPAL_UNBLOCK_ADMIN}" == "1" ]; then
  echo "==> Unblocking admin user."
  $drush uublk 1 -q || true
  $drush uli
fi

# Run custom drupal site install scripts.
# The files should be located in ""${APP}"/scripts/custom/" directory and must have
# "drupal-install-site-" prefix and ".sh" extension.
if [ -d "${APP}/scripts/custom" ]; then
  for file in "${APP}"/scripts/custom/drupal-install-site-*.sh; do
    if [ -f "${file}" ]; then
      . "${file}"
    fi
  done
  unset file
fi

if [ -n "${DRUPAL_BUILD_WITH_MAINTENANCE_MODE}" ]; then
  echo "==> Disabled maintenance mode."
  $drush ${DRUSH_ALIAS} vset maintenance_mode 0 --format=boolean
fi
