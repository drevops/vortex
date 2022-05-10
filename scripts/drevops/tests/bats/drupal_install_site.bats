#!/usr/bin/env bats
##
# Unit tests for install-site.sh
#
#shellcheck disable=SC2030,SC2031

load _helper
load _helper_drevops

assert_drupal_install_site_info(){
  assert_output_contains "==> Started site installation."
  assert_output_contains "  > App dir                      : ${LOCAL_REPO_DIR}"
  assert_output_contains "  > Site name                    : Example site"
  assert_output_contains "  > Site email                   : webmaster@example.com"
  assert_output_contains "  > Profile                      : standard"
  assert_output_contains "  > Install from profile         : ${1:-0}"
  assert_output_contains "  > Overwrite existing DB        : ${2:-0}"
  assert_output_contains "  > Skip sanitization            : ${3:-0}"
  assert_output_contains "  > Use maintenance mode         : ${4:-1}"
  assert_output_contains "  > Skip post-install operations : ${5:-0}"
  assert_output_contains "  > Private files directory      : ${LOCAL_REPO_DIR}/docroot/sites/default/files/private"
  assert_output_contains "  > Config path                  : ${LOCAL_REPO_DIR}/config/default"
  assert_output_contains "  > Config directory label       :"
  assert_output_contains "  > DB dump file path            : ${LOCAL_REPO_DIR}/.data/db.sql"
  assert_output_contains "  > Existing site found          : ${6:-0}"
  assert_output_contains "  > Configuration files present  : ${7:-0}"
}

@test "Site install: DB; no site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "${LOCAL_REPO_DIR}/.data"
  touch "${LOCAL_REPO_DIR}/.data/db.sql"

  mock_drush=$(mock_command "drush")
  # Drush version.
  mock_set_output "${mock_drush}" "Mocked drush version" 1
  # Bootstrap Drupal.
  mock_set_output "${mock_drush}" "fail" 2
  # 2 calls to import DB from file.
  mock_set_status "${mock_drush}" 0 3
  mock_set_status "${mock_drush}" 0 4
  # Enable maintenance mode.
  mock_set_status "${mock_drush}" 0 5
  # Running updates.
  mock_set_status "${mock_drush}" 0 6
  # Rebuild cache.
  mock_set_status "${mock_drush}" 0 7
  # Environment name.
  mock_set_output "${mock_drush}" "ci" 8
  # List all drush commands to check for pciu command presence.
  mock_set_output "${mock_drush}" "none" 9
  # Sanitization commands.
  mock_set_status "${mock_drush}" 0 10
  mock_set_status "${mock_drush}" 0 11
  mock_set_status "${mock_drush}" 0 12
  mock_set_status "${mock_drush}" 0 13
  # Environment name from custom script.
  mock_set_output "${mock_drush}" "ci" 14
  # Disable maintenance mode.
  mock_set_status "${mock_drush}" 0 15
  # 4 calls when generating login link.
  mock_set_output "${mock_drush}" "none" 16
  mock_set_output "${mock_drush}" "admin" 17
  mock_set_status "${mock_drush}" 0 18
  mock_set_output "${mock_drush}" "MOCK_ONE_TIME_LINK" 19

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  assert_equal "status --fields=bootstrap" "$(mock_get_call_args "${mock_drush}" 2)"

  assert_drupal_install_site_info 0 0 0 1 0 0 0

  assert_output_contains "==> Creating private files directory."
  assert_output_contains "  > Successfully created private files directory."

  assert_output_contains "========================================"
  assert_output_contains "==> Installing site from the database dump file."
  assert_output_contains "  > Dump file: ${LOCAL_REPO_DIR}/.data/db.sql"
  assert_output_contains "  > Existing site was not found."
  assert_output_contains "  > The site content will be imported from the database dump file."
  assert_output_contains "  > Successfully imported database from dump file."
  assert_output_contains "========================================"

  assert_equal "-q sql-drop -y" "$(mock_get_call_args "${mock_drush}" 3)"
  assert_equal "sqlc" "$(mock_get_call_args "${mock_drush}" 4)"

  assert_output_not_contains "ERROR: Unable to import database from file."
  assert_output_not_contains "       Dump file ${LOCAL_REPO_DIR}/.data/db.sql does not exist."
  assert_output_not_contains "       Site content was not changed."

  assert_output_not_contains "  > Existing site content will be removed and new content will be imported from the database dump file."
  assert_output_not_contains "  > Existing site was found."
  assert_output_not_contains "  > Site content will be preserved."
  assert_output_not_contains "  > Sanitization will be skipped for an existing database."
  assert_output_not_contains "==> Installing site from the profile."
  assert_output_not_contains "  > Existing site content will be removed and new content will be created from the profile."
  assert_output_not_contains "  > The site content will be created from the profile."

  assert_output_not_contains "==> Skipped running of post-install operations."

  assert_output_contains "==> Running post-install operations."

  assert_output_contains "==> Enabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 1 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 5)"

  assert_output_contains "==> Running database updates."
  assert_equal "updb -y" "$(mock_get_call_args "${mock_drush}" 6)"

  assert_output_contains "==> Importing Drupal configuration if it exists."
  assert_output_not_contains "  > Updated site UUID from the configuration with"
  assert_output_not_contains "  > Importing configuration"
  assert_output_not_contains "  > Importing config_split configuration."
  assert_output_contains "  > Configuration files were not found in ${LOCAL_REPO_DIR}/config/default"

  assert_output_contains "==> Rebuilding cache."
  assert_equal "cr" "$(mock_get_call_args "${mock_drush}" 7)"

  assert_output_contains "==> Current Drupal environment: ci"
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 8)"

  assert_output_not_contains "==> Running post config import updates."
  assert_equal "list" "$(mock_get_call_args "${mock_drush}" 9)"

  # Sanitization is skipped for the existing database.
  assert_output_contains "==> Started database sanitization."
  assert_output_contains "  > Sanitizing database using drush sql-sanitize."
  assert_equal "sql-sanitize --sanitize-password=MOCK_DB_SANITIZE_PASSWORD --sanitize-email=user+%uid@localhost -y" "$(mock_get_call_args "${mock_drush}" 10)"
  assert_output_not_contains "  > Updating username with user email."
  assert_output_contains "  > Applying custom sanitization commands from file ${LOCAL_REPO_DIR}/scripts/sanitize.sql"
  assert_equal "sql-query --file=${LOCAL_REPO_DIR}/scripts/sanitize.sql" "$(mock_get_call_args "${mock_drush}" 11)"
  assert_output_contains "  > Resetting user 0 username and email."
  assert_equal "sql-query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';" "$(mock_get_call_args "${mock_drush}" 12)"
  assert_equal "sql-query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';" "$(mock_get_call_args "${mock_drush}" 13)"
  assert_output_not_contains "  > Updating user 1 email"
  assert_output_contains "==> Finished database sanitization."
  assert_output_not_contains "==> Skipped database sanitization."

  assert_output_contains "==> Running custom post-install script ${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-example-operations.sh."
  assert_output_contains "  > Started example post site install operations."
  assert_output_contains "    Executing example operations in non-production environment."
  assert_output_contains "  > Finished example post site install operations."
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 14)"

  assert_output_contains "==> Disabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 0 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 15)"

  # One-time login link.
  assert_equal "pml --status=enabled" "$(mock_get_call_args "${mock_drush}" 16)"
  assert_equal "sqlq SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" "$(mock_get_call_args "${mock_drush}" 17)"
  assert_equal "-q -- uublk admin" "$(mock_get_call_args "${mock_drush}" 18)"
  assert_equal "uli" "$(mock_get_call_args "${mock_drush}" 19)"
  assert_output_contains "MOCK_ONE_TIME_LINK"

  assert_output_contains "==> Finished site installation."

  popd >/dev/null || exit 1
}

@test "Site install: DB; existing site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export CI=1

  mkdir "${LOCAL_REPO_DIR}/.data"
  touch "${LOCAL_REPO_DIR}/.data/db.sql"

  mock_drush=$(mock_command "drush")
  # Drush version.
  mock_set_output "${mock_drush}" "Mocked drush version" 1
  # Bootstrap Drupal.
  mock_set_output "${mock_drush}" "Successful" 2
  # Enable maintenance mode.
  mock_set_status "${mock_drush}" 0 3
  # Running updates.
  mock_set_status "${mock_drush}" 0 4
  # Rebuild cache.
  mock_set_status "${mock_drush}" 0 5
  # Environment name.
  mock_set_output "${mock_drush}" "ci" 6
  # List all drush commands to check for pciu command presence.
  mock_set_output "${mock_drush}" "none" 7
  # Environment name from custom script.
  mock_set_output "${mock_drush}" "ci" 8
  # Disable maintenance mode.
  mock_set_status "${mock_drush}" 0 9
  # 4 calls when generating login link.
  mock_set_output "${mock_drush}" "none" 10
  mock_set_output "${mock_drush}" "admin" 11
  mock_set_status "${mock_drush}" 0 12
  mock_set_output "${mock_drush}" "MOCK_ONE_TIME_LINK" 13

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  assert_equal "status --fields=bootstrap" "$(mock_get_call_args "${mock_drush}" 2)"

  assert_drupal_install_site_info 0 0 0 1 0 1 0

  assert_output_contains "==> Creating private files directory."
  assert_output_contains "  > Successfully created private files directory."

  assert_output_contains "========================================"
  assert_output_contains "==> Installing site from the database dump file."
  assert_output_contains "  > Dump file: ${LOCAL_REPO_DIR}/.data/db.sql"
  assert_output_contains "  > Existing site was found."
  assert_output_contains "  > Site content will be preserved."
  assert_output_contains "  > Sanitization will be skipped for an existing database."
  assert_output_contains "========================================"

  assert_output_not_contains "  > Existing site content will be removed and new content will be imported from the database dump file."
  assert_output_not_contains "  > Existing site was not found."
  assert_output_not_contains "  > The site content will be imported from the database dump file."
  assert_output_not_contains "  > Successfully imported database from dump file."
  assert_output_not_contains "==> Installing site from the profile."
  assert_output_not_contains "  > Existing site content will be removed and new content will be created from the profile."
  assert_output_not_contains "  > The site content will be created from the profile."

  assert_output_not_contains "==> Skipped running of post-install operations."

  assert_output_contains "==> Running post-install operations."

  assert_output_contains "==> Enabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 1 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 3)"

  assert_output_contains "==> Running database updates."
  assert_equal "updb -y" "$(mock_get_call_args "${mock_drush}" 4)"

  assert_output_contains "==> Importing Drupal configuration if it exists."
  assert_output_not_contains "  > Updated site UUID from the configuration with"
  assert_output_not_contains "  > Importing configuration"
  assert_output_not_contains "  > Importing config_split configuration."
  assert_output_contains "  > Configuration files were not found in ${LOCAL_REPO_DIR}/config/default"

  assert_output_contains "==> Rebuilding cache."
  assert_equal "cr" "$(mock_get_call_args "${mock_drush}" 5)"

  assert_output_contains "==> Current Drupal environment: ci"
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 6)"

  assert_output_not_contains "==> Running post config import updates."
  assert_equal "list" "$(mock_get_call_args "${mock_drush}" 7)"

  # Sanitization is skipped for the existing database.
  assert_output_contains "==> Skipped database sanitization."
  assert_output_not_contains "==> Started database sanitization."
  assert_output_not_contains "  > Sanitizing database using drush sql-sanitize."
  assert_output_not_contains "  > Updating username with user email."
  assert_output_not_contains "  > Applying custom sanitization commands from file "
  assert_output_not_contains "  > Resetting user 0 username and email."
  assert_output_not_contains "  > Updating user 1 email"
  assert_output_not_contains "==> Finished database sanitization."

  assert_output_contains "==> Running custom post-install script ${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-example-operations.sh."
  assert_output_contains "  > Started example post site install operations."
  assert_output_contains "    Executing example operations in non-production environment."
  assert_output_contains "  > Finished example post site install operations."
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 8)"

  assert_output_contains "==> Disabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 0 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 9)"

  # One-time login link.
  assert_equal "pml --status=enabled" "$(mock_get_call_args "${mock_drush}" 10)"
  assert_equal "sqlq SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" "$(mock_get_call_args "${mock_drush}" 11)"
  assert_equal "-q -- uublk admin" "$(mock_get_call_args "${mock_drush}" 12)"
  assert_equal "uli" "$(mock_get_call_args "${mock_drush}" 13)"
  assert_output_contains "MOCK_ONE_TIME_LINK"

  assert_output_contains "==> Finished site installation."

  popd >/dev/null || exit 1
}

@test "Site install: DB; existing site; overwrite" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB=1
  export DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "${LOCAL_REPO_DIR}/.data"
  touch "${LOCAL_REPO_DIR}/.data/db.sql"

  mock_drush=$(mock_command "drush")
  # Drush version.
  mock_set_output "${mock_drush}" "Mocked drush version" 1
  # Bootstrap Drupal.
  mock_set_output "${mock_drush}" "Successful" 2
  # 2 calls to import DB from file.
  mock_set_status "${mock_drush}" 0 3
  mock_set_status "${mock_drush}" 0 4
  # Enable maintenance mode.
  mock_set_status "${mock_drush}" 0 5
  # Running updates.
  mock_set_status "${mock_drush}" 0 6
  # Rebuild cache.
  mock_set_status "${mock_drush}" 0 7
  # Environment name.
  mock_set_output "${mock_drush}" "ci" 8
  # List all drush commands to check for pciu command presence.
  mock_set_output "${mock_drush}" "none" 9
  # Sanitization commands.
  mock_set_status "${mock_drush}" 0 10
  mock_set_status "${mock_drush}" 0 11
  mock_set_status "${mock_drush}" 0 12
  mock_set_status "${mock_drush}" 0 13
  # Environment name from custom script.
  mock_set_output "${mock_drush}" "ci" 14
  # Disable maintenance mode.
  mock_set_status "${mock_drush}" 0 15
  # 4 calls when generating login link.
  mock_set_output "${mock_drush}" "none" 16
  mock_set_output "${mock_drush}" "admin" 17
  mock_set_status "${mock_drush}" 0 18
  mock_set_output "${mock_drush}" "MOCK_ONE_TIME_LINK" 19

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  assert_equal "status --fields=bootstrap" "$(mock_get_call_args "${mock_drush}" 2)"

  assert_drupal_install_site_info 0 1 0 1 0 1 0

  assert_output_contains "==> Creating private files directory."
  assert_output_contains "  > Successfully created private files directory."

  assert_output_contains "========================================"
  assert_output_contains "==> Installing site from the database dump file."
  assert_output_contains "  > Dump file: ${LOCAL_REPO_DIR}/.data/db.sql"
  assert_output_contains "  > Existing site was found."
  assert_output_contains "  > Existing site content will be removed and new content will be imported from the database dump file."
  assert_output_contains "  > Successfully imported database from dump file."
  assert_output_contains "========================================"

  assert_equal "-q sql-drop -y" "$(mock_get_call_args "${mock_drush}" 3)"
  assert_equal "sqlc" "$(mock_get_call_args "${mock_drush}" 4)"

  assert_output_not_contains "  > Site content will be preserved."
  assert_output_not_contains "  > Sanitization will be skipped for an existing database."
  assert_output_not_contains "  > Existing site was not found."
  assert_output_not_contains "  > The site content will be imported from the database dump file."
  assert_output_not_contains "==> Installing site from the profile."
  assert_output_not_contains "  > Existing site content will be removed and new content will be created from the profile."
  assert_output_not_contains "  > The site content will be created from the profile."

  assert_output_not_contains "==> Skipped running of post-install operations."

  assert_output_contains "==> Running post-install operations."

  assert_output_contains "==> Enabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 1 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 5)"

  assert_output_contains "==> Running database updates."
  assert_equal "updb -y" "$(mock_get_call_args "${mock_drush}" 6)"

  assert_output_contains "==> Importing Drupal configuration if it exists."
  assert_output_not_contains "  > Updated site UUID from the configuration with"
  assert_output_not_contains "  > Importing configuration"
  assert_output_not_contains "  > Importing config_split configuration."
  assert_output_contains "  > Configuration files were not found in ${LOCAL_REPO_DIR}/config/default"

  assert_output_contains "==> Rebuilding cache."
  assert_equal "cr" "$(mock_get_call_args "${mock_drush}" 7)"

  assert_output_contains "==> Current Drupal environment: ci"
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 8)"

  assert_output_not_contains "==> Running post config import updates."
  assert_equal "list" "$(mock_get_call_args "${mock_drush}" 9)"

  assert_output_contains "==> Started database sanitization."
  assert_output_contains "  > Sanitizing database using drush sql-sanitize."
  assert_equal "sql-sanitize --sanitize-password=MOCK_DB_SANITIZE_PASSWORD --sanitize-email=user+%uid@localhost -y" "$(mock_get_call_args "${mock_drush}" 10)"
  assert_output_not_contains "  > Updating username with user email."
  assert_output_contains "  > Applying custom sanitization commands from file ${LOCAL_REPO_DIR}/scripts/sanitize.sql"
  assert_equal "sql-query --file=${LOCAL_REPO_DIR}/scripts/sanitize.sql" "$(mock_get_call_args "${mock_drush}" 11)"
  assert_output_contains "  > Resetting user 0 username and email."
  assert_equal "sql-query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';" "$(mock_get_call_args "${mock_drush}" 12)"
  assert_equal "sql-query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';" "$(mock_get_call_args "${mock_drush}" 13)"
  assert_output_not_contains "  > Updating user 1 email"
  assert_output_contains "==> Finished database sanitization."
  assert_output_not_contains "==> Skipped database sanitization."

  assert_output_contains "==> Running custom post-install script ${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-example-operations.sh."
  assert_output_contains "  > Started example post site install operations."
  assert_output_contains "    Executing example operations in non-production environment."
  assert_output_contains "  > Finished example post site install operations."
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 14)"

  assert_output_contains "==> Disabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 0 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 15)"

  # One-time login link.
  assert_equal "pml --status=enabled" "$(mock_get_call_args "${mock_drush}" 16)"
  assert_equal "sqlq SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" "$(mock_get_call_args "${mock_drush}" 17)"
  assert_equal "-q -- uublk admin" "$(mock_get_call_args "${mock_drush}" 18)"
  assert_equal "uli" "$(mock_get_call_args "${mock_drush}" 19)"
  assert_output_contains "MOCK_ONE_TIME_LINK"

  assert_output_contains "==> Finished site installation."

  popd >/dev/null || exit 1
}

@test "Site install: profile; no site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export DREVOPS_DRUPAL_INSTALL_FROM_PROFILE=1
  export DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mock_drush=$(mock_command "drush")
  # Drush version.
  mock_set_output "${mock_drush}" "Mocked drush version" 1
  # Bootstrap Drupal.
  mock_set_output "${mock_drush}" "fail" 2
  # 2 calls to install site from profile.
  mock_set_status "${mock_drush}" 0 3
  mock_set_status "${mock_drush}" 0 4
  # Enable maintenance mode.
  mock_set_status "${mock_drush}" 0 5
  # Running updates.
  mock_set_status "${mock_drush}" 0 6
  # Rebuild cache.
  mock_set_status "${mock_drush}" 0 7
  # Environment name.
  mock_set_output "${mock_drush}" "ci" 8
  # List all drush commands to check for pciu command presence.
  mock_set_output "${mock_drush}" "none" 9
  # Sanitization commands.
  mock_set_status "${mock_drush}" 0 10
  mock_set_status "${mock_drush}" 0 11
  mock_set_status "${mock_drush}" 0 12
  mock_set_status "${mock_drush}" 0 13
  # Environment name from custom script.
  mock_set_output "${mock_drush}" "ci" 14
  # Disable maintenance mode.
  mock_set_status "${mock_drush}" 0 15
  # 4 calls when generating login link.
  mock_set_output "${mock_drush}" "none" 16
  mock_set_output "${mock_drush}" "admin" 17
  mock_set_status "${mock_drush}" 0 18
  mock_set_output "${mock_drush}" "MOCK_ONE_TIME_LINK" 19

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  assert_equal "status --fields=bootstrap" "$(mock_get_call_args "${mock_drush}" 2)"

  assert_drupal_install_site_info 1 0 0 1 0 0 0

  assert_output_contains "==> Creating private files directory."
  assert_output_contains "  > Successfully created private files directory."

  assert_output_contains "========================================"
  assert_output_contains "==> Installing site from the profile."
  assert_output_contains "  > Profile: standard."
  assert_output_contains "  > Existing site was not found."
  assert_output_contains "  > The site content will be created from the profile."
  assert_output_contains "  > Successfully installed a site from profile."
  assert_output_contains "========================================"

  assert_equal "-q sql-drop -y" "$(mock_get_call_args "${mock_drush}" 3)"
  assert_equal "si standard --site-name=Example site --site-mail=webmaster@example.com --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL -y" "$(mock_get_call_args "${mock_drush}" 4)"

  assert_output_not_contains "ERROR: Unable to import database from file."
  assert_output_not_contains "       Dump file ${LOCAL_REPO_DIR}/.data/db.sql does not exist."
  assert_output_not_contains "       Site content was not changed."

  assert_output_not_contains "  > Existing site content will be removed and new content will be imported from the database dump file."
  assert_output_not_contains "  > Existing site was found."
  assert_output_not_contains "  > Site content will be preserved."
  assert_output_not_contains "  > Sanitization will be skipped for an existing database."
  assert_output_not_contains "==> Installing site from the database dump file."
  assert_output_not_contains "  > Existing site content will be removed and new content will be created from the profile."
  assert_output_not_contains "  > The site content will be imported from the database dump file."
  assert_output_not_contains "  > Successfully imported database from dump file."

  assert_output_not_contains "==> Skipped running of post-install operations."

  assert_output_contains "==> Running post-install operations."

  assert_output_contains "==> Enabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 1 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 5)"

  assert_output_contains "==> Running database updates."
  assert_equal "updb -y" "$(mock_get_call_args "${mock_drush}" 6)"

  assert_output_contains "==> Importing Drupal configuration if it exists."
  assert_output_not_contains "  > Updated site UUID from the configuration with"
  assert_output_not_contains "  > Importing configuration"
  assert_output_not_contains "  > Importing config_split configuration."
  assert_output_contains "  > Configuration files were not found in ${LOCAL_REPO_DIR}/config/default"

  assert_output_contains "==> Rebuilding cache."
  assert_equal "cr" "$(mock_get_call_args "${mock_drush}" 7)"

  assert_output_contains "==> Current Drupal environment: ci"
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 8)"

  assert_output_not_contains "==> Running post config import updates."
  assert_equal "list" "$(mock_get_call_args "${mock_drush}" 9)"

  assert_output_contains "==> Started database sanitization."
  assert_output_contains "  > Sanitizing database using drush sql-sanitize."
  assert_equal "sql-sanitize --sanitize-password=MOCK_DB_SANITIZE_PASSWORD --sanitize-email=user+%uid@localhost -y" "$(mock_get_call_args "${mock_drush}" 10)"
  assert_output_not_contains "  > Updating username with user email."
  assert_output_contains "  > Applying custom sanitization commands from file ${LOCAL_REPO_DIR}/scripts/sanitize.sql"
  assert_equal "sql-query --file=${LOCAL_REPO_DIR}/scripts/sanitize.sql" "$(mock_get_call_args "${mock_drush}" 11)"
  assert_output_contains "  > Resetting user 0 username and email."
  assert_equal "sql-query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';" "$(mock_get_call_args "${mock_drush}" 12)"
  assert_equal "sql-query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';" "$(mock_get_call_args "${mock_drush}" 13)"
  assert_output_not_contains "  > Updating user 1 email"
  assert_output_contains "==> Finished database sanitization."
  assert_output_not_contains "==> Skipped database sanitization."

  assert_output_contains "==> Running custom post-install script ${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-example-operations.sh."
  assert_output_contains "  > Started example post site install operations."
  assert_output_contains "    Executing example operations in non-production environment."
  assert_output_contains "  > Finished example post site install operations."
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 14)"

  assert_output_contains "==> Disabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 0 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 15)"

  # One-time login link.
  assert_equal "pml --status=enabled" "$(mock_get_call_args "${mock_drush}" 16)"
  assert_equal "sqlq SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" "$(mock_get_call_args "${mock_drush}" 17)"
  assert_equal "-q -- uublk admin" "$(mock_get_call_args "${mock_drush}" 18)"
  assert_equal "uli" "$(mock_get_call_args "${mock_drush}" 19)"
  assert_output_contains "MOCK_ONE_TIME_LINK"

  assert_output_contains "==> Finished site installation."

  popd >/dev/null || exit 1
}

@test "Site install: profile; existing site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export DREVOPS_DRUPAL_INSTALL_FROM_PROFILE=1
  export CI=1

  mock_drush=$(mock_command "drush")
  # Drush version.
  mock_set_output "${mock_drush}" "Mocked drush version" 1
  # Bootstrap Drupal.
  mock_set_output "${mock_drush}" "Successful" 2
  # Enable maintenance mode.
  mock_set_status "${mock_drush}" 0 3
  # Running updates.
  mock_set_status "${mock_drush}" 0 4
  # Rebuild cache.
  mock_set_status "${mock_drush}" 0 5
  # Environment name.
  mock_set_output "${mock_drush}" "ci" 6
  # List all drush commands to check for pciu command presence.
  mock_set_output "${mock_drush}" "none" 7
  # Environment name from custom script.
  mock_set_output "${mock_drush}" "ci" 8
  # Disable maintenance mode.
  mock_set_status "${mock_drush}" 0 9
  # 4 calls when generating login link.
  mock_set_output "${mock_drush}" "none" 10
  mock_set_output "${mock_drush}" "admin" 11
  mock_set_status "${mock_drush}" 0 12
  mock_set_output "${mock_drush}" "MOCK_ONE_TIME_LINK" 13

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  assert_equal "status --fields=bootstrap" "$(mock_get_call_args "${mock_drush}" 2)"

  assert_drupal_install_site_info 1 0 0 1 0 1 0

  assert_output_contains "==> Creating private files directory."
  assert_output_contains "  > Successfully created private files directory."

  assert_output_contains "========================================"
  assert_output_contains "==> Installing site from the profile."
  assert_output_contains "  > Profile: standard."
  assert_output_contains "  > Existing site was found."
  assert_output_contains "  > Site content will be preserved."
  assert_output_contains "  > Sanitization will be skipped for an existing database."
  assert_output_contains "========================================"

  assert_output_not_contains "  > Successfully installed a site from profile."
  assert_output_not_contains "  > Existing site content will be removed and new content will be imported from the database dump file."
  assert_output_not_contains "  > Existing site was not found."
  assert_output_not_contains "  > The site content will be imported from the database dump file."
  assert_output_not_contains "  > Successfully imported database from dump file."
  assert_output_not_contains "  > Existing site content will be removed and new content will be created from the profile."
  assert_output_not_contains "  > The site content will be created from the profile."
  assert_output_not_contains "==> Installing site from the database dump file."
  assert_output_not_contains "==> Skipped running of post-install operations."

  assert_output_contains "==> Running post-install operations."

  assert_output_contains "==> Enabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 1 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 3)"

  assert_output_contains "==> Running database updates."
  assert_equal "updb -y" "$(mock_get_call_args "${mock_drush}" 4)"

  assert_output_contains "==> Importing Drupal configuration if it exists."
  assert_output_not_contains "  > Updated site UUID from the configuration with"
  assert_output_not_contains "  > Importing configuration"
  assert_output_not_contains "  > Importing config_split configuration."
  assert_output_contains "  > Configuration files were not found in ${LOCAL_REPO_DIR}/config/default"

  assert_output_contains "==> Rebuilding cache."
  assert_equal "cr" "$(mock_get_call_args "${mock_drush}" 5)"

  assert_output_contains "==> Current Drupal environment: ci"
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 6)"

  assert_output_not_contains "==> Running post config import updates."
  assert_equal "list" "$(mock_get_call_args "${mock_drush}" 7)"

  # Sanitization is skipped for the existing database.
  assert_output_contains "==> Skipped database sanitization."
  assert_output_not_contains "==> Started database sanitization."
  assert_output_not_contains "  > Sanitizing database using drush sql-sanitize."
  assert_output_not_contains "  > Updating username with user email."
  assert_output_not_contains "  > Applying custom sanitization commands from file "
  assert_output_not_contains "  > Resetting user 0 username and email."
  assert_output_not_contains "  > Updating user 1 email"
  assert_output_not_contains "==> Finished database sanitization."

  assert_output_contains "==> Running custom post-install script ${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-example-operations.sh."
  assert_output_contains "  > Started example post site install operations."
  assert_output_contains "    Executing example operations in non-production environment."
  assert_output_contains "  > Finished example post site install operations."
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 8)"

  assert_output_contains "==> Disabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 0 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 9)"

  # One-time login link.
  assert_equal "pml --status=enabled" "$(mock_get_call_args "${mock_drush}" 10)"
  assert_equal "sqlq SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" "$(mock_get_call_args "${mock_drush}" 11)"
  assert_equal "-q -- uublk admin" "$(mock_get_call_args "${mock_drush}" 12)"
  assert_equal "uli" "$(mock_get_call_args "${mock_drush}" 13)"
  assert_output_contains "MOCK_ONE_TIME_LINK"

  assert_output_contains "==> Finished site installation."

  popd >/dev/null || exit 1
}

@test "Site install: profile; existing site; overwrite" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export DREVOPS_DRUPAL_INSTALL_FROM_PROFILE=1
  export DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB=1
  export DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mock_drush=$(mock_command "drush")
  # Drush version.
  mock_set_output "${mock_drush}" "Mocked drush version" 1
  # Bootstrap Drupal.
  mock_set_output "${mock_drush}" "Successful" 2
  # 2 calls to install site from profile.
  mock_set_status "${mock_drush}" 0 3
  mock_set_status "${mock_drush}" 0 4
  # Enable maintenance mode.
  mock_set_status "${mock_drush}" 0 5
  # Running updates.
  mock_set_status "${mock_drush}" 0 6
  # Rebuild cache.
  mock_set_status "${mock_drush}" 0 7
  # Environment name.
  mock_set_output "${mock_drush}" "ci" 8
  # List all drush commands to check for pciu command presence.
  mock_set_output "${mock_drush}" "none" 9
  # Sanitization commands.
  mock_set_status "${mock_drush}" 0 10
  mock_set_status "${mock_drush}" 0 11
  mock_set_status "${mock_drush}" 0 12
  mock_set_status "${mock_drush}" 0 13
  # Environment name from custom script.
  mock_set_output "${mock_drush}" "ci" 14
  # Disable maintenance mode.
  mock_set_status "${mock_drush}" 0 15
  # 4 calls when generating login link.
  mock_set_output "${mock_drush}" "none" 16
  mock_set_output "${mock_drush}" "admin" 17
  mock_set_status "${mock_drush}" 0 18
  mock_set_output "${mock_drush}" "MOCK_ONE_TIME_LINK" 19

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  assert_equal "status --fields=bootstrap" "$(mock_get_call_args "${mock_drush}" 2)"

  assert_drupal_install_site_info 1 1 0 1 0 1 0

  assert_output_contains "==> Creating private files directory."
  assert_output_contains "  > Successfully created private files directory."

  assert_output_contains "========================================"
  assert_output_contains "==> Installing site from the profile."
  assert_output_contains "  > Profile: standard."
  assert_output_contains "  > Existing site was found."
  assert_output_contains "  > Existing site content will be removed and new content will be created from the profile."
  assert_output_contains "  > Successfully installed a site from profile."
  assert_output_contains "========================================"

  assert_equal "-q sql-drop -y" "$(mock_get_call_args "${mock_drush}" 3)"
  assert_equal "si standard --site-name=Example site --site-mail=webmaster@example.com --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL -y" "$(mock_get_call_args "${mock_drush}" 4)"

  assert_output_not_contains "  > Site content will be preserved."
  assert_output_not_contains "  > Sanitization will be skipped for an existing database."
  assert_output_not_contains "  > Existing site content will be removed and new content will be imported from the database dump file."
  assert_output_not_contains "  > Existing site was not found."
  assert_output_not_contains "  > The site content will be imported from the database dump file."
  assert_output_not_contains "  > Successfully imported database from dump file."
  assert_output_not_contains "  > The site content will be created from the profile."
  assert_output_not_contains "==> Installing site from the database dump file."
  assert_output_not_contains "==> Skipped running of post-install operations."

  assert_output_contains "==> Running post-install operations."

  assert_output_contains "==> Enabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 1 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 5)"

  assert_output_contains "==> Running database updates."
  assert_equal "updb -y" "$(mock_get_call_args "${mock_drush}" 6)"

  assert_output_contains "==> Importing Drupal configuration if it exists."
  assert_output_not_contains "  > Updated site UUID from the configuration with"
  assert_output_not_contains "  > Importing configuration"
  assert_output_not_contains "  > Importing config_split configuration."
  assert_output_contains "  > Configuration files were not found in ${LOCAL_REPO_DIR}/config/default"

  assert_output_contains "==> Rebuilding cache."
  assert_equal "cr" "$(mock_get_call_args "${mock_drush}" 7)"

  assert_output_contains "==> Current Drupal environment: ci"
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 8)"

  assert_output_not_contains "==> Running post config import updates."
  assert_equal "list" "$(mock_get_call_args "${mock_drush}" 9)"

  assert_output_contains "==> Started database sanitization."
  assert_output_contains "  > Sanitizing database using drush sql-sanitize."
  assert_equal "sql-sanitize --sanitize-password=MOCK_DB_SANITIZE_PASSWORD --sanitize-email=user+%uid@localhost -y" "$(mock_get_call_args "${mock_drush}" 10)"
  assert_output_not_contains "  > Updating username with user email."
  assert_output_contains "  > Applying custom sanitization commands from file ${LOCAL_REPO_DIR}/scripts/sanitize.sql"
  assert_equal "sql-query --file=${LOCAL_REPO_DIR}/scripts/sanitize.sql" "$(mock_get_call_args "${mock_drush}" 11)"
  assert_output_contains "  > Resetting user 0 username and email."
  assert_equal "sql-query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';" "$(mock_get_call_args "${mock_drush}" 12)"
  assert_equal "sql-query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';" "$(mock_get_call_args "${mock_drush}" 13)"
  assert_output_not_contains "  > Updating user 1 email"
  assert_output_contains "==> Finished database sanitization."
  assert_output_not_contains "==> Skipped database sanitization."

  assert_output_contains "==> Running custom post-install script ${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-example-operations.sh."
  assert_output_contains "  > Started example post site install operations."
  assert_output_contains "    Executing example operations in non-production environment."
  assert_output_contains "  > Finished example post site install operations."
  assert_equal "ev print \Drupal\core\Site\Settings::get('environment');" "$(mock_get_call_args "${mock_drush}" 14)"

  assert_output_contains "==> Disabled maintenance mode."
  assert_equal "state:set system.maintenance_mode 0 --input-format=integer" "$(mock_get_call_args "${mock_drush}" 15)"

  # One-time login link.
  assert_equal "pml --status=enabled" "$(mock_get_call_args "${mock_drush}" 16)"
  assert_equal "sqlq SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" "$(mock_get_call_args "${mock_drush}" 17)"
  assert_equal "-q -- uublk admin" "$(mock_get_call_args "${mock_drush}" 18)"
  assert_equal "uli" "$(mock_get_call_args "${mock_drush}" 19)"
  assert_output_contains "MOCK_ONE_TIME_LINK"

  assert_output_contains "==> Finished site installation."

  popd >/dev/null || exit 1
}
