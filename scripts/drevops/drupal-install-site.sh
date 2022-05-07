#!/usr/bin/env bash
##
# Install site from database or profile, run updates and import configuration.
#
# shellcheck disable=SC2086,SC2002,SC2235,SC1090

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
DREVOPS_APP="${APP:-/app}"

# Drupal site name
DREVOPS_DRUPAL_SITE_NAME="${DREVOPS_DRUPAL_SITE_NAME:-Example site}"

# Drupal site name
DREVOPS_DRUPAL_SITE_EMAIL="${DREVOPS_DRUPAL_SITE_EMAIL:-webmaster@example.com}"

# Profile machine name.
DREVOPS_DRUPAL_PROFILE="${DREVOPS_DRUPAL_PROFILE:-standard}"

# Path to configuration directory.
DREVOPS_DRUPAL_CONFIG_PATH="${DREVOPS_DRUPAL_CONFIG_PATH:-${DREVOPS_APP}/config/default}"

# Config label.
DREVOPS_DRUPAL_CONFIG_LABEL="${DREVOPS_DRUPAL_CONFIG_LABEL:-}"

# Path to private files.
DREVOPS_DRUPAL_PRIVATE_FILES="${DREVOPS_DRUPAL_PRIVATE_FILES:-${DREVOPS_APP}/docroot/sites/default/files/private}"

# Directory with database dump file.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-${DREVOPS_APP}/.data}"

# Database dump file name.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

# Flag to export database before import.
DREVOPS_DB_EXPORT_BEFORE_IMPORT="${DREVOPS_DB_EXPORT_BEFORE_IMPORT:-0}"

# Flag to skip DB import.
DREVOPS_DRUPAL_SKIP_DB_IMPORT="${DREVOPS_DRUPAL_SKIP_DB_IMPORT:-}"

# Flag to skip running post DB import commands.
DREVOPS_DRUPAL_SKIP_POST_DB_IMPORT="${DREVOPS_DRUPAL_SKIP_POST_DB_IMPORT:-}"

# Flag to force fresh install even if the site exists.
DREVOPS_DRUPAL_FORCE_FRESH_INSTALL="${DREVOPS_DRUPAL_FORCE_FRESH_INSTALL:-}"

# Flag to always overwrite existing database. Usually set to 0 in deployed
# environments.
DREVOPS_DB_OVERWRITE_EXISTING="${DREVOPS_DB_OVERWRITE_EXISTING:-1}"

# ------------------------------------------------------------------------------

echo "==> Installing site."

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

# Create private files directory.
mkdir -p "${DREVOPS_DRUPAL_PRIVATE_FILES}"

# Export database before importing, if the flag is set.
# Useful to automatically store database dump before starting site rebuild.
[ "${DREVOPS_DB_EXPORT_BEFORE_IMPORT}" -eq 1 ] && "${DREVOPS_APP}/scripts/drevops/export-db-file.sh"

site_is_installed="$($drush status --fields=bootstrap | grep -q "Successful" && echo "1" || echo "0")"

# Install site from the database dump or from profile.
if
  # Not skipping DB import AND
  [ "${DREVOPS_DRUPAL_SKIP_DB_IMPORT}" != "1" ] &&
  # DB dump file exists AND
  [ -f "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" ] &&
  # Site is not installed OR allowed to overwrite existing site.
  ([ "${site_is_installed}" != "1" ] || [ "${DREVOPS_DB_OVERWRITE_EXISTING}" = "1" ])
then
  echo "****************************************"
  echo "==> Importing database from dump."
  echo "  > Using DB dump ${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}."
  echo "****************************************"
  DREVOPS_DB_DIR="${DREVOPS_DB_DIR}" DREVOPS_DB_FILE="${DREVOPS_DB_FILE}" "${DREVOPS_APP}/scripts/drevops/import-db-file.sh"
elif
  # If site is installed AND
  [ "${site_is_installed}" = "1" ] &&
  # Not allowed to forcefully install from profile.
  [ "${DREVOPS_DRUPAL_FORCE_FRESH_INSTALL}" != "1" ]
then
  echo "****************************************"
  echo "==> Existing site found."
  echo "  > Database will be preserved."
  echo "  > Re-run with DREVOPS_DRUPAL_FORCE_FRESH_INSTALL=1 to forcefully re-install."
  echo "****************************************"
else
  echo "****************************************"
  echo "==> Existing site not found."
  echo "  > Installing site from profile ${DREVOPS_DRUPAL_PROFILE}."
  echo "****************************************"

  # Scan for configuration files.
  if ls "${DREVOPS_DRUPAL_CONFIG_PATH}"/*.yml >/dev/null 2>&1; then
    echo "==> Using found configuration files."
    # Install from profile and configuration.
    $drush si "${DREVOPS_DRUPAL_PROFILE}" -y --account-name=admin --site-name="${DREVOPS_DRUPAL_SITE_NAME}" --config-dir=${DREVOPS_DRUPAL_CONFIG_PATH} install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL
    # Run updates.
    $drush updb -y
    # Mark to skip any other operations as the site is now fully built.
    DREVOPS_DRUPAL_SKIP_POST_DB_IMPORT=1
  else
    # Install from profile with default configuration.
    $drush si "${DREVOPS_DRUPAL_PROFILE}" -y --account-name=admin --site-name="${DREVOPS_DRUPAL_SITE_NAME}" --site-mail="${DREVOPS_DRUPAL_SITE_EMAIL}" install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL
  fi
fi

# Skip running of post DB import scripts and finish installation.
# Useful when need to capture database state before any updates ran (for
# example, DB caching in CI).
if [ "${DREVOPS_DRUPAL_SKIP_POST_DB_IMPORT}" = "1" ]; then
  echo "==> Skipped running of post DB init commands."
  # Rebuild cache.
  $drush cr
  # Sanitize DB.
  "${DREVOPS_APP}/scripts/drevops/drupal-sanitize-db.sh"
  # Exit as there is nothing that should be ran after this.
  exit 0
fi

echo "==> Running post DB init commands."

if [ "${DREVOPS_DRUPAL_BUILD_WITH_MAINTENANCE_MODE}" = "1" ]; then
  echo "==> Enabled maintenance mode."
  $drush state:set system.maintenance_mode 1 --input-format=integer
fi

# Run updates.
$drush updb -y

# Import Drupal configuration, if configuration files exist.
if ls "${DREVOPS_DRUPAL_CONFIG_PATH}"/*.yml >/dev/null 2>&1; then
  # Update site UUID from the configuration.
  if [ -f "${DREVOPS_DRUPAL_CONFIG_PATH}/system.site.yml" ]; then
    config_uuid="$(cat "${DREVOPS_DRUPAL_CONFIG_PATH}/system.site.yml" | grep uuid | tail -c +7 | head -c 36)"
    $drush config-set system.site uuid "${config_uuid}" --yes
  fi

  # Import configuration.
  $drush cim "${DREVOPS_DRUPAL_CONFIG_LABEL}" -y

  # Import config_split configuration if the module is installed.
  if $drush pml --status=enabled | grep -q config_split; then
    # Drush command does not return correct code on failed split, so not
    # failing on import for the non-existing environment is currently
    # the same as not failing on failed import.
    # @see https://www.drupal.org/project/config_split/issues/3171819
    $drush config-split:import -y "${environment:-}" || true
  fi
else
  echo "==> Configuration was not found in ${DREVOPS_DRUPAL_CONFIG_PATH} path."
fi

# Rebuild cache.
$drush cr

echo -n "==> Current Drupal environment: "
environment="$($drush ev "print \Drupal\core\Site\Settings::get('environment');")"
echo "${environment}" && echo

# Run post-config import updates for the cases when updates rely on imported configuration.
# @see PostConfigImportUpdateHelper::registerPostConfigImportUpdate()
if $drush list | grep -q pciu; then
  echo "==> Running post config import updates."
  $drush post-config-import-update
fi

# Sanitize database.
"${DREVOPS_APP}/scripts/drevops/drupal-sanitize-db.sh"

# User mail and name for use 0 could have been sanitized - resetting it.
echo "  > Resetting user 0 username and email."
$drush sql-query "UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
$drush sql-query "UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"

# User mail could have been sanitized - setting it back to a pre-defined mail.
if [ -n "${DREVOPS_DRUPAL_ADMIN_EMAIL}" ]; then
  echo "  > Updating user 1 email"
  $drush sql-query "UPDATE \`users_field_data\` SET mail = '${DREVOPS_DRUPAL_ADMIN_EMAIL}' WHERE uid = '1';"
fi

if [ "${DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_WITH_EMAIL}" = "1" ]; then
  echo "  > Updating user 1 username with user email"
  $drush sql-query "UPDATE \`users_field_data\` set users_field_data.name=users_field_data.mail WHERE uid = '1';"
fi

# Generate a one-time login link.
"${DREVOPS_APP}/scripts/drevops/drupal-login.sh"

# Run custom drupal site install scripts.
# The files should be located in ""${DREVOPS_APP}"/scripts/custom/" directory and must have
# "drupal-install-site-" prefix and ".sh" extension.
if [ -d "${DREVOPS_APP}/scripts/custom" ]; then
  for file in "${DREVOPS_APP}"/scripts/custom/drupal-install-site-*.sh; do
    if [ -f "${file}" ]; then
      echo "==> Running custom post-install script ${file}."
      . "${file}"
    fi
  done
  unset file
fi

if [ "${DREVOPS_DRUPAL_BUILD_WITH_MAINTENANCE_MODE}" = "1" ]; then
  echo "==> Disabled maintenance mode."
  $drush state:set system.maintenance_mode 0 --input-format=integer
fi
