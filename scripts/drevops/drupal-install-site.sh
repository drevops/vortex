#!/usr/bin/env bash
##
# Install site from database or profile, run updates and import configuration.
#
# This script has excessive verbose output to make it easy to debug site
# installations and deployments.
#
# shellcheck disable=SC2086,SC2002,SC2235,SC1090,SC2012,SC2015

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

# Flag to skip site installation.
DREVOPS_DRUPAL_INSTALL_SKIP="${DREVOPS_DRUPAL_INSTALL_SKIP:-}"

# Path to the application.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# Name of the webroot directory with Drupal installation.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

# Drupal site name
DREVOPS_DRUPAL_SITE_NAME="${DREVOPS_DRUPAL_SITE_NAME:-Example site}"

# Drupal site name
DREVOPS_DRUPAL_SITE_EMAIL="${DREVOPS_DRUPAL_SITE_EMAIL:-webmaster@example.com}"

# Profile machine name.
DREVOPS_DRUPAL_PROFILE="${DREVOPS_DRUPAL_PROFILE:-standard}"

# Path to configuration directory.
DREVOPS_DRUPAL_CONFIG_PATH="${DREVOPS_DRUPAL_CONFIG_PATH:-${DREVOPS_APP}/config/default}"

# Path to private files.
DREVOPS_DRUPAL_PRIVATE_FILES="${DREVOPS_DRUPAL_PRIVATE_FILES:-${DREVOPS_APP}/${DREVOPS_WEBROOT}/sites/default/files/private}"

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

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

drush_opts=(-y)
[ -z "${DREVOPS_DEBUG:-}" ] && drush_opts+=(-q)

info "Started site installation."

[ -n "${DREVOPS_DRUPAL_INSTALL_SKIP}" ] && pass "Skipping site installation" && exit 0

# Use local or global Drush, giving priority to a local Drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

# Convert DB dir starting with './' to a full path.
[ "${DREVOPS_DB_DIR#./}" != "$DREVOPS_DB_DIR" ] && DREVOPS_DB_DIR="$(pwd)${DREVOPS_DB_DIR#.}"

# Print installation information.
# Note that "flag" variable values are printed as-is to make it easy to visually
# assert their values.
echo
note "App dir                      : ${DREVOPS_APP}"
note "Web root dir                 : ${DREVOPS_WEBROOT}"
note "Site name                    : ${DREVOPS_DRUPAL_SITE_NAME}"
note "Site email                   : ${DREVOPS_DRUPAL_SITE_EMAIL}"
note "Profile                      : ${DREVOPS_DRUPAL_PROFILE}"
note "Private files directory      : ${DREVOPS_DRUPAL_PRIVATE_FILES}"
note "Config path                  : ${DREVOPS_DRUPAL_CONFIG_PATH}"
note "DB dump file path            : ${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE} ($([ -f "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" ] && echo "present" || echo "absent"))"
if [ -n "${DREVOPS_DB_DOCKER_IMAGE:-}" ]; then
  note "DB dump Docker image         : ${DREVOPS_DB_DOCKER_IMAGE}"
fi
echo
note "Drush binary                 : ${drush}"
note "Drush version                : $($drush --version)"
note "Drupal core version          : $($drush status --field=drupal-version)"
echo
note "Install from profile         : $([ "${DREVOPS_DRUPAL_INSTALL_FROM_PROFILE}" = "1" ] && echo "Yes" || echo "No")"
note "Overwrite existing DB        : $([ "${DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB}" = "1" ] && echo "Yes" || echo "No")"
note "Skip sanitization            : $([ "${DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP}" = "1" ] && echo "Yes" || echo "No")"
note "Use maintenance mode         : $([ "${DREVOPS_DRUPAL_INSTALL_USE_MAINTENANCE_MODE}" = "1" ] && echo "Yes" || echo "No")"
note "Skip post-install operations : $([ "${DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP}" = "1" ] && echo "Yes" || echo "No")"

site_has_config="$(test "$(ls -1 $DREVOPS_DRUPAL_CONFIG_PATH/*.yml 2>/dev/null | wc -l | tr -d ' ')" -gt 0 && echo "1" || echo "0")"
note "Configuration files present  : $([ "${site_has_config}" = "1" ] && echo "Yes" || echo "No")"

site_is_installed="$($drush status --fields=bootstrap | grep -q "Successful" && echo "1" || echo "0")"
note "Existing site found          : $([ "${site_is_installed}" = "1" ] && echo "Yes" || echo "No")"

echo

if [ -n "${DREVOPS_DRUPAL_PRIVATE_FILES}" ]; then
  info "Creating private files directory."
  if [ -d "${DREVOPS_DRUPAL_PRIVATE_FILES}" ]; then
    pass "Private files directory already exists."
  else
    mkdir -p "${DREVOPS_DRUPAL_PRIVATE_FILES}"
    if [ -d "${DREVOPS_DRUPAL_PRIVATE_FILES}" ]; then
      pass "Created private files directory."
    else
      fail "Unable to create private files directory."
      exit 1
    fi
  fi
fi

#
# Install site by importing from the database dump file.
#
install_import() {
  if [ ! -f "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" ]; then
    echo
    fail "Unable to import database from file."
    note "Dump file ${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE} does not exist."
    note "Site content was not changed."
    exit 1
  fi

  $drush "${drush_opts[@]}" sql:drop

  $drush "${drush_opts[@]}" sql:cli <"${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"

  pass "Imported database from the dump file."
}

#
# Install site from profile.
#
install_profile() {
  opts=()

  [ -z "${DREVOPS_DEBUG:-}" ] && opts+=(-q)

  opts+=(
    -y
    "${DREVOPS_DRUPAL_PROFILE}"
    --site-name="${DREVOPS_DRUPAL_SITE_NAME}"
    --site-mail="${DREVOPS_DRUPAL_SITE_EMAIL}"
    --account-name=admin
    install_configure_form.enable_update_status_module=NULL
    install_configure_form.enable_update_status_emails=NULL
  )

  [ -n "${DREVOPS_DRUPAL_ADMIN_EMAIL:-}" ] && opts+=(--account-mail="${DREVOPS_DRUPAL_ADMIN_EMAIL:-}")

  [ "${site_has_config}" = "1" ] && opts+=(--existing-config)

  # Database may exist in non-bootstrappable state - truncate it.
  $drush "${drush_opts[@]}" sql:drop || true

  $drush site:install "${opts[@]}"

  pass "Installed a site from the profile."
}

# Install site from DB dump or profile.
#
# The code block below has explicit if-else conditions and verbose output to
# ensure that this significant operation is executed correctly and has
# sufficient output for debugging.
echo

if [ "${DREVOPS_DRUPAL_INSTALL_FROM_PROFILE}" != "1" ]; then
  info "Installing site from the database dump file."
  note "Dump file path: ${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"

  if [ "${site_is_installed}" = "1" ]; then
    note "Existing site was found."

    if [ "${DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB}" = "1" ]; then
      note "Existing site content will be removed and new content will be imported from the database dump file."
      install_import
    else
      note "Site content will be preserved."
      note "Sanitization will be skipped for an existing database."
      export DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP=1
    fi
  else
    note "Existing site was not found."
    note "The site content will be imported from the database dump file."
    install_import
  fi
else
  info "Installing site from the profile."
  note "Profile: ${DREVOPS_DRUPAL_PROFILE}."

  if [ "${site_is_installed}" = "1" ]; then
    note "Existing site was found."

    if [ "${DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB}" = "1" ]; then
      note "Existing site content will be removed and new content will be created from the profile."
      install_profile
    else
      note "Site content will be preserved."
      note "Sanitization will be skipped for an existing database."
      export DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP=1
    fi
  else
    note "Existing site was not found."
    note "The site content will be created from the profile."
    install_profile
  fi
fi

echo

if [ "${DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP}" = "1" ]; then
  info "Skipped running of post-install operations."
  echo
  info "Finished site installation."
  exit 0
fi

if [ "${DREVOPS_DRUPAL_INSTALL_USE_MAINTENANCE_MODE}" = "1" ]; then
  info "Enabling maintenance mode."
  $drush "${drush_opts[@]}" state:set system.maintenance_mode 1 --input-format=integer
  pass "Enabled maintenance mode."
  echo
fi

echo -n "[INFO] Current Drupal environment: "
environment="$($drush php:eval "print \Drupal\core\Site\Settings::get('environment');")" && echo "${environment}"
echo

info "Running database updates."
$drush -y updatedb --no-cache-clear
pass "Completed running database updates."
echo

info "Importing Drupal configuration if it exists."
if [ "${site_has_config}" = "1" ]; then
  if [ -f "${DREVOPS_DRUPAL_CONFIG_PATH}/system.site.yml" ]; then
    config_uuid="$(cat "${DREVOPS_DRUPAL_CONFIG_PATH}/system.site.yml" | grep uuid | tail -c +7 | head -c 36)"
    $drush "${drush_opts[@]}" config-set system.site uuid "${config_uuid}"
    pass "Updated site UUID from the configuration with ${config_uuid}."
  fi

  info "Importing configuration"
  $drush "${drush_opts[@]}" config:import
  pass "Completed configuration import."

  # Import config_split configuration if the module is installed.
  if $drush pm:list --status=enabled | grep -q config_split; then
    info "Importing config_split configuration."
    # Drush command does not return correct code on failed split, so not
    # failing on import for the non-existing environment is currently
    # the same as not failing on failed import.
    # @see https://www.drupal.org/project/config_split/issues/3171819
    $drush "${drush_opts[@]}" config-split:import "${environment}" || true
    pass "Config-split Completed configuration import."
  fi
else
  pass "Configuration files were not found in ${DREVOPS_DRUPAL_CONFIG_PATH} path."
fi
echo

info "Rebuilding cache."
$drush "${drush_opts[@]}" cache:rebuild
pass "Cache was rebuilt."
echo

# @see https://www.drush.org/latest/deploycommand/
if $drush list | grep -q deploy; then
  info "Running updates after configuration import via Drush deploy."
  $drush -y deploy:hook
  pass "Completed updates after configuration import via Drush deploy."
  echo
fi

if [ "${DREVOPS_DRUPAL_INSTALL_DB_SANITIZE_SKIP}" != "1" ]; then
  # Sanitize database.
  "${DREVOPS_APP}/scripts/drevops/drupal-sanitize-db.sh"
else
  info "Skipped database sanitization."
  echo
fi

# Run custom drupal site install scripts.
# The files should be located in ""${DREVOPS_APP}"/scripts/custom/" directory
# and must have "drupal-install-site-" prefix and ".sh" extension.
if [ -d "${DREVOPS_APP}/scripts/custom" ]; then
  for file in "${DREVOPS_APP}"/scripts/custom/drupal-install-site-*.sh; do
    if [ -f "${file}" ]; then
      info "Running custom post-install script ${file}."
      . "${file}"
      pass "Completed running of custom post-install script ${file}."
      echo
    fi
  done
  unset file
fi

if [ "${DREVOPS_DRUPAL_INSTALL_USE_MAINTENANCE_MODE}" = "1" ]; then
  info "Disabling maintenance mode."
  $drush "${drush_opts[@]}" state:set system.maintenance_mode 0 --input-format=integer
  pass "Disabled maintenance mode."
  echo
fi

info "One-time login link."
"${DREVOPS_APP}/scripts/drevops/drupal-login.sh"
echo

pass "Finished site installation."
