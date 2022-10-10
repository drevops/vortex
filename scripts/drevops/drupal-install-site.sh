#!/usr/bin/env bash
##
# Install site from database or profile, run updates and import configuration.
#
# This script has excessive verbose output to make it easy to debug site
# installations and deployments.
#
# shellcheck disable=SC2086,SC2002,SC2235,SC1090,SC2012

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to skip site installation.
DREVOPS_DRUPAL_INSTALL_SKIP="${DREVOPS_DRUPAL_INSTALL_SKIP:-}"

# Path to the application.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# Drupal site name
DREVOPS_DRUPAL_SITE_NAME="${DREVOPS_DRUPAL_SITE_NAME:-Example site}"

# Drupal site name
DREVOPS_DRUPAL_SITE_EMAIL="${DREVOPS_DRUPAL_SITE_EMAIL:-webmaster@example.com}"

# Profile machine name.
DREVOPS_DRUPAL_PROFILE="${DREVOPS_DRUPAL_PROFILE:-standard}"

# Path to configuration directory.
DREVOPS_DRUPAL_CONFIG_PATH="${DREVOPS_DRUPAL_CONFIG_PATH:-${DREVOPS_APP}/config/default}"

# Path to private files.
DREVOPS_DRUPAL_PRIVATE_FILES="${DREVOPS_DRUPAL_PRIVATE_FILES:-${DREVOPS_APP}/docroot/sites/default/files/private}"

# Directory with database dump file.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-${DREVOPS_APP}/.data}"

# Database dump file name.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

# Install a site from profile instead of database file dump.
DREVOPS_DRUPAL_INSTALL_FROM_PROFILE="${DREVOPS_DRUPAL_INSTALL_FROM_PROFILE:-0}"

# Flag to always overwrite existing database. Usually set to 0 in deployed
# environments.
DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB="${DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB:-0}"

# Skip database sanitization.
DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP="${DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP:-0}"

# Put the site into a maintenance mode during site installation phase.
DREVOPS_DRUPAL_INSTALL_USE_MAINTENANCE_MODE="${DREVOPS_DRUPAL_INSTALL_USE_MAINTENANCE_MODE:-1}"

# Flag to skip running post DB import commands.
# Useful to only import the database from file (or install from profile) and not
# perform any additional operations. For example, when need to capture database
# state before any updates ran (for example, DB caching in CI).
DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP="${DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP:-0}"

# ------------------------------------------------------------------------------

drush_opts=(-y)
[ -z "${DREVOPS_DEBUG}" ] && drush_opts+=(-q)

echo "INFO Started site installation."

[ -n "${DREVOPS_DRUPAL_INSTALL_SKIP}" ] && echo "  OK Skipping site installation" && exit 0

# Use local or global Drush, giving priority to a local Drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

# Print installation information.
# Note that "flag" variable values are printed as-is to make it easy to visually
# assert their values.
echo
echo "     App dir                      : ${DREVOPS_APP}"
echo "     Site name                    : ${DREVOPS_DRUPAL_SITE_NAME}"
echo "     Site email                   : ${DREVOPS_DRUPAL_SITE_EMAIL}"
echo "     Profile                      : ${DREVOPS_DRUPAL_PROFILE}"
echo "     Install from profile         : ${DREVOPS_DRUPAL_INSTALL_FROM_PROFILE}"
echo "     Overwrite existing DB        : ${DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB}"
echo "     Skip sanitization            : ${DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP}"
echo "     Use maintenance mode         : ${DREVOPS_DRUPAL_INSTALL_USE_MAINTENANCE_MODE}"
echo "     Skip post-install operations : ${DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP}"
echo "     Private files directory      : ${DREVOPS_DRUPAL_PRIVATE_FILES}"
echo "     Config path                  : ${DREVOPS_DRUPAL_CONFIG_PATH}"
echo "     DB dump file path            : ${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"
if [ -n "${DREVOPS_DB_DOCKER_IMAGE}" ]; then
  echo "     DB dump Docker image         : ${DREVOPS_DB_DOCKER_IMAGE}"
fi
echo "     Drush binary                 : ${drush}"
echo "     Drush version                : $($drush --version)"
echo "     Drupal core version          : $($drush status --field=drupal-version)"

site_is_installed="$($drush status --fields=bootstrap | grep -q "Successful" && echo "1" || echo "0")"
echo "     Existing site found          : ${site_is_installed}"

site_has_config="$(test "$(ls -1 $DREVOPS_DRUPAL_CONFIG_PATH/*.yml 2>/dev/null | wc -l | tr -d ' ')" -gt 0 && echo "1" || echo "0")"
echo "     Configuration files present  : ${site_has_config}"

echo

if [ -n "${DREVOPS_DRUPAL_PRIVATE_FILES}" ]; then
  echo "INFO Creating private files directory."
  if [ -d "${DREVOPS_DRUPAL_PRIVATE_FILES}" ]; then
    echo "  OK Private files directory already exists."
  else
    mkdir -p "${DREVOPS_DRUPAL_PRIVATE_FILES}"
    if [ -d "${DREVOPS_DRUPAL_PRIVATE_FILES}" ]; then
      echo "  OK Successfully created private files directory."
    else
      echo "ERROR Unable to create private files directory."
      exit 1
    fi
  fi
fi

#
# Install site by importing from the database dump file.
#
install_import() {
  if [ ! -f "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" ]; then
    echo "ERROR Unable to import database from file."
    echo "       Dump file ${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE} does not exist."
    echo "       Site content was not changed."
    exit 1
  fi

  $drush "${drush_opts[@]}" sql-drop
  $drush "${drush_opts[@]}" sqlc <"${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"
  echo "  OK Successfully imported database from the dump file."
}

#
# Install site from profile.
#
install_profile() {
  opts=()

  [ -z "${DREVOPS_DEBUG}" ] && opts+=(-q)

  opts+=(
    -y
    "${DREVOPS_DRUPAL_PROFILE}"
    --site-name="${DREVOPS_DRUPAL_SITE_NAME}"
    --site-mail="${DREVOPS_DRUPAL_SITE_EMAIL}"
    --account-name=admin
    install_configure_form.enable_update_status_module=NULL
    install_configure_form.enable_update_status_emails=NULL
  )

  [ -n "${DREVOPS_DRUPAL_ADMIN_EMAIL}" ] && opts+=(--account-mail="${DREVOPS_DRUPAL_ADMIN_EMAIL}")
  [ "${site_has_config}" = "1" ] && opts+=(--existing-config)

  # Database may exist in non-bootstrappable state - truncuate it.
  $drush "${drush_opts[@]}" sql-drop || true
  $drush si "${opts[@]}"
  echo "  OK Successfully installed a site from the profile."
}

# Install site from DB dump or profile.
#
# The code block below has explicit if-else conditions and verbose output to
# ensure that this significant operation is executed correctly and has
# sufficient output for debugging.
echo

if [ "${DREVOPS_DRUPAL_INSTALL_FROM_PROFILE}" != "1" ]; then
  echo "INFO Installing site from the database dump file."
  echo "     Dump file: ${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"

  if [ "${site_is_installed}" = "1" ]; then
      echo "     Existing site was found."

    if [ "${DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB}" = "1" ]; then
      echo "     Existing site content will be removed and new content will be imported from the database dump file."
      install_import
    else
      echo "     Site content will be preserved."
      echo "     Sanitization will be skipped for an existing database."
      export DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP=1
    fi
  else
    echo "     Existing site was not found."
    echo "     The site content will be imported from the database dump file."
    install_import
  fi
else
  echo "INFO Installing site from the profile."
  echo "     Profile: ${DREVOPS_DRUPAL_PROFILE}."

  if [ "${site_is_installed}" = "1" ]; then
    echo "     Existing site was found."

    if [ "${DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB}" = "1" ]; then
      echo "     Existing site content will be removed and new content will be created from the profile."
      install_profile
    else
      echo "     Site content will be preserved."
      echo "     Sanitization will be skipped for an existing database."
      export DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP=1
    fi
  else
    echo "     Existing site was not found."
    echo "     The site content will be created from the profile."
    install_profile
  fi
fi

echo

if [ "${DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP}" = "1" ]; then
  echo "INFO Skipped running of post-install operations."
  echo
  echo "INFO Finished site installation."
  exit 0
fi

if [ "${DREVOPS_DRUPAL_INSTALL_USE_MAINTENANCE_MODE}" = "1" ]; then
  echo "INFO Enabling maintenance mode."
  $drush "${drush_opts[@]}" state:set system.maintenance_mode 1 --input-format=integer
  echo "  OK Enabled maintenance mode."
  echo
fi

echo "INFO Running database updates."
$drush -y updb --no-cache-clear
echo "  OK Updates were run successfully."
echo

echo "INFO Importing Drupal configuration if it exists."
if [ "${site_has_config}" = "1" ]; then
  if [ -f "${DREVOPS_DRUPAL_CONFIG_PATH}/system.site.yml" ]; then
    config_uuid="$(cat "${DREVOPS_DRUPAL_CONFIG_PATH}/system.site.yml" | grep uuid | tail -c +7 | head -c 36)"
    $drush "${drush_opts[@]}" config-set system.site uuid "${config_uuid}"
    echo "  OK Updated site UUID from the configuration with ${config_uuid}."
  fi

  echo "INFO Importing configuration"
  $drush "${drush_opts[@]}" config:import
  echo "  OK Configuration was imported successfully."

  # Import config_split configuration if the module is installed.
  if $drush pm:list --status=enabled | grep -q config_split; then
    echo "INFO Importing config_split configuration."
    # Drush command does not return correct code on failed split, so not
    # failing on import for the non-existing environment is currently
    # the same as not failing on failed import.
    # @see https://www.drupal.org/project/config_split/issues/3171819
    $drush "${drush_opts[@]}" config-split:import "${environment:-}" || true
    echo "  OK Config-split configuration was imported successfully."
  fi
else
  echo "  OK Configuration files were not found in ${DREVOPS_DRUPAL_CONFIG_PATH} path."
fi
echo

echo "INFO Rebuilding cache."
$drush "${drush_opts[@]}" cache:rebuild
echo "  OK Cache was rebuilt."
echo

echo -n "INFO Current Drupal environment: "
environment="$($drush php:eval "print \Drupal\core\Site\Settings::get('environment');")" && echo "${environment}"
echo

# @see https://www.drush.org/latest/deploycommand/
if $drush list | grep -q deploy; then
  echo "INFO Running post config import updates via Drush deploy."
  $drush -y deploy:hook
  echo "  OK Post config import updates ran successfully."
  echo
fi

if [ "${DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP}" != "1" ]; then
  # Sanitize database.
  "${DREVOPS_APP}/scripts/drevops/drupal-sanitize-db.sh"
else
  echo "INFO Skipped database sanitization."
  echo
fi

# Run custom drupal site install scripts.
# The files should be located in ""${DREVOPS_APP}"/scripts/custom/" directory and must have
# "drupal-install-site-" prefix and ".sh" extension.
if [ -d "${DREVOPS_APP}/scripts/custom" ]; then
  for file in "${DREVOPS_APP}"/scripts/custom/drupal-install-site-*.sh; do
    if [ -f "${file}" ]; then
      echo "INFO Running custom post-install script ${file}."
      . "${file}"
      echo "  OK Custom post-install script ${file} ran successfully."
      echo
    fi
  done
  unset file
fi

if [ "${DREVOPS_DRUPAL_INSTALL_USE_MAINTENANCE_MODE}" = "1" ]; then
  echo "INFO Disabling maintenance mode."
  $drush "${drush_opts[@]}" state:set system.maintenance_mode 0 --input-format=integer
  echo "  OK Disabled maintenance mode."
  echo
fi

# Generate a one-time login link.
echo
"${DREVOPS_APP}/scripts/drevops/drupal-login.sh"
echo

echo "INFO Finished site installation."
