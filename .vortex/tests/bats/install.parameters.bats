#!/usr/bin/env bats
#
# Tests for installer parameters for quiet and interactive installation.
#
# - proceed switch (used to stop installation)
# - default values
# - normalisation
# - discovery
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash

@test "Install parameters: empty dir; proceed switch; quiet" {
  export VORTEX_INSTALL_PROCEED=0
  output=$(run_installer_quiet)
  assert_output_contains "WELCOME TO VORTEX QUIET INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"
  assert_files_not_present_common
}

@test "Install parameters: empty dir; proceed switch; interactive" {
  export VORTEX_INSTALL_PROCEED=0

  answers=(
    "Star wars" # name
    "nothing"   # machine_name
    "nothing"   # org
    "nothing"   # org_machine_name
    "nothing"   # module_prefix
    "nothing"   # profile
    "nothing"   # theme
    "nothing"   # URL
    "nothing"   # webroot
    "nothing"   # provision_use_profile
    "nothing"   # database_download_source
    "nothing"   # database_store_type
    "nothing"   # override_existing_db
    "nothing"   # deploy_type
    "nothing"   # preserve_ftp
    "nothing"   # preserve_acquia
    "nothing"   # preserve_lagoon
    "nothing"   # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"
  assert_files_not_present_common
}

@test "Install parameters: empty dir; defaults; quiet" {
  export VORTEX_INSTALL_PROCEED=0

  output=$(run_installer_quiet)
  assert_output_contains "WELCOME TO VORTEX QUIET INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "                          Name:  Star wars     "
  assert_output_contains "                  Machine name:  star_wars     "
  assert_output_contains "                  Organisation:  Star wars Org "
  assert_output_contains "     Organisation machine name:  star_wars_org "
  assert_output_contains "                 Module prefix:  sw            "
  assert_output_contains "                       Profile:  standard      "
  assert_output_contains "                    Theme name:  star_wars     "
  assert_output_contains "                           URL:  star-wars.com "
  assert_output_contains "                      Web root:  web           "
  assert_output_contains "          Install from profile:  No            "
  assert_output_contains "      Database download source:  curl          "
  assert_output_contains "           Database store type:  file          "
  assert_output_contains "    Override existing database:  No            "
  assert_output_contains "                    Deployment:  artifact      "
  assert_output_contains "               FTP integration:  Disabled      "
  assert_output_contains "            Acquia integration:  Disabled      "
  assert_output_contains "            Lagoon integration:  Disabled      "
  assert_output_contains "       RenovateBot integration:  Enabled       "
  assert_output_contains "     Preserve docs in comments:  Yes           "
  assert_output_contains "      Preserve Vortex comments:  No            "
}

@test "Install parameters: empty dir; defaults; interactive" {
  export VORTEX_INSTALL_PROCEED=0

  answers=(
    "Star wars" # name
    "nothing"   # machine_name
    "nothing"   # org
    "nothing"   # org_machine_name
    "nothing"   # module_prefix
    "nothing"   # profile
    "nothing"   # theme
    "nothing"   # URL
    "nothing"   # webroot
    "nothing"   # provision_use_profile
    "nothing"   # database_download_source
    "nothing"   # database_store_type
    "nothing"   # deploy_type
    "nothing"   # preserve_ftp
    "nothing"   # preserve_acquia
    "nothing"   # preserve_lagoon
    "nothing"   # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "                          Name:  Star wars     "
  assert_output_contains "                  Machine name:  star_wars     "
  assert_output_contains "                  Organisation:  Star wars Org "
  assert_output_contains "     Organisation machine name:  star_wars_org "
  assert_output_contains "                 Module prefix:  sw            "
  assert_output_contains "                       Profile:  standard      "
  assert_output_contains "                    Theme name:  star_wars     "
  assert_output_contains "                           URL:  star-wars.com "
  assert_output_contains "                      Web root:  web           "
  assert_output_contains "          Install from profile:  No            "
  assert_output_contains "      Database download source:  curl          "
  assert_output_contains "           Database store type:  file          "
  assert_output_contains "    Override existing database:  No            "
  assert_output_contains "                    Deployment:  artifact      "
  assert_output_contains "               FTP integration:  Disabled      "
  assert_output_contains "            Acquia integration:  Disabled      "
  assert_output_contains "            Lagoon integration:  Disabled      "
  assert_output_contains "       RenovateBot integration:  Enabled       "
  assert_output_contains "     Preserve docs in comments:  Yes           "
  assert_output_contains "      Preserve Vortex comments:  No            "
}

# Note that there is no quiet test for this scenario.
@test "Install parameters: empty dir; overrides and normalisation; interactive" {
  export VORTEX_INSTALL_PROCEED=0

  answers=(
    "star Wars"              # name
    "star wars MaCHine"      # machine_name
    "The Empire"             # org
    "the new empire"         # morh_machine_name
    "s W"                    # module_prefix
    "S w Profile"            # profile
    "light saber"            # theme
    "resistance forever.com" # URL
    "nothing"                # webroot
    "nah"                    # provision_use_profile
    "something"              # download_db_type
    "other thing"            # database_download_source
    "dunno"                  # database_store_type
    "nnnnnno"                #override_existing_db
    "nnnooo"                 # deploy_type
    "nope"                   # preserve_ftp
    "dunno"                  # preserve_acquia
    "nah"                    # preserve_lagoon
    "never"                  # preserve_renovatebot
    "nnnooo"                 # preserve_doc_comments
    "nooo"                   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed."

  assert_output_contains "                          Name:  Star wars              "
  assert_output_contains "                  Machine name:  star_wars_machine      "
  assert_output_contains "                  Organisation:  The Empire             "
  assert_output_contains "     Organisation machine name:  the_new_empire         "
  assert_output_contains "                 Module prefix:  s_w                    "
  assert_output_contains "                       Profile:  s_w_profile            "
  assert_output_contains "                    Theme name:  light_saber            "
  assert_output_contains "                           URL:  resistance-forever.com "
  assert_output_contains "                      Web root:  web                    "
  assert_output_contains "          Install from profile:  No                     "
  assert_output_contains "      Database download source:  curl                   "
  assert_output_contains "           Database store type:  file                   "
  assert_output_contains "    Override existing database:  No                     "
  assert_output_contains "                    Deployment:  Disabled               "
  assert_output_contains "               FTP integration:  Disabled               "
  assert_output_contains "            Acquia integration:  Disabled               "
  assert_output_contains "            Lagoon integration:  Disabled               "
  assert_output_contains "       RenovateBot integration:  Disabled               "
  assert_output_contains "     Preserve docs in comments:  No                     "
  assert_output_contains "      Preserve Vortex comments:  No                     "
}

@test "Install parameters: empty dir; overrides and normalisation; interactive; custom webroot" {
  export VORTEX_INSTALL_PROCEED=0

  answers=(
    "star Wars"              # name
    "star wars MaCHine"      # machine_name
    "The Empire"             # org
    "the new empire"         # morh_machine_name
    "s W"                    # module_prefix
    "S w Profile"            # profile
    "light saber"            # theme
    "resistance forever.com" # URL
    "rootdoc"                # webroot
    "nah"                    # provision_use_profile
    "something"              # download_db_type
    "other thing"            # database_download_source
    "dunno"                  # database_store_type
    "nnnnnno"                #override_existing_db
    "nnnooo"                 # deploy_type
    "nope"                   # preserve_ftp
    "dunno"                  # preserve_acquia
    "nah"                    # preserve_lagoon
    "never"                  # preserve_renovatebot
    "nnnooo"                 # preserve_doc_comments
    "nooo"                   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed."

  assert_output_contains "                          Name:  Star wars              "
  assert_output_contains "                  Machine name:  star_wars_machine      "
  assert_output_contains "                  Organisation:  The Empire             "
  assert_output_contains "     Organisation machine name:  the_new_empire         "
  assert_output_contains "                 Module prefix:  s_w                    "
  assert_output_contains "                       Profile:  s_w_profile            "
  assert_output_contains "                    Theme name:  light_saber            "
  assert_output_contains "                           URL:  resistance-forever.com "
  assert_output_contains "                      Web root:  rootdoc                "
  assert_output_contains "          Install from profile:  No                     "
  assert_output_contains "      Database download source:  curl                   "
  assert_output_contains "           Database store type:  file                   "
  assert_output_contains "    Override existing database:  No                     "
  assert_output_contains "                    Deployment:  Disabled               "
  assert_output_contains "               FTP integration:  Disabled               "
  assert_output_contains "            Acquia integration:  Disabled               "
  assert_output_contains "            Lagoon integration:  Disabled               "
  assert_output_contains "       RenovateBot integration:  Disabled               "
  assert_output_contains "     Preserve docs in comments:  No                     "
  assert_output_contains "      Preserve Vortex comments:  No                     "
}

@test "Install parameters: pre-installed; overrides, normalisation and discovery; quiet" {
  export VORTEX_INSTALL_PROCEED=0

  fixture_preinstalled web

  output=$(run_installer_quiet)
  assert_output_contains "WELCOME TO VORTEX QUIET INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "                          Name:  Resistance new site          "
  assert_output_contains "                  Machine name:  resistance_site              "
  assert_output_contains "                  Organisation:  The Resistance               "
  assert_output_contains "     Organisation machine name:  the_next_resistance          "
  assert_output_contains "                 Module prefix:  another_resist               "
  assert_output_contains "                       Profile:  standard                     "
  assert_output_contains "          Install from profile:  No                           "
  assert_output_contains "                    Theme name:  resisting                    "
  assert_output_contains "                           URL:  www.resistance-star-wars.com "
  assert_output_contains "                      Web root:  web                          "
  assert_output_contains "      Database download source:  curl                         "
  assert_output_contains "           Database store type:  file                         "
  assert_output_contains "    Override existing database:  No                           "
  assert_output_contains "                    Deployment:  artifact                     "
  assert_output_contains "               FTP integration:  Disabled                     "
  assert_output_contains "            Acquia integration:  Enabled                      "
  assert_output_contains "            Lagoon integration:  Enabled                      "
  assert_output_contains "       RenovateBot integration:  Enabled                      "
  assert_output_contains "     Preserve docs in comments:  Yes                          "
  assert_output_contains "      Preserve Vortex comments:  Yes                          "
}

@test "Install parameters: pre-installed; overrides, normalisation and discovery; interactive; accepting suggested values" {
  export VORTEX_INSTALL_PROCEED=0

  fixture_preinstalled web

  answers=(
    "nothing" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # webroot
    "nothing" # provision_use_profile
    "nothing" # database_download_source
    "nothing" # database_store_type
    "nothing" # override_existing_db
    "nothing" # deploy_type
    "nothing" # preserve_ftp
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_renovatebot
    "nothing" # preserve_doc_comments
    "nothing" # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  # Note that user input takes precedence over discovered values.
  assert_output_contains "                          Name:  Resistance new site          "
  assert_output_contains "                  Machine name:  resistance_site              "
  assert_output_contains "                  Organisation:  The Resistance               "
  assert_output_contains "     Organisation machine name:  the_next_resistance          "
  assert_output_contains "                 Module prefix:  another_resist               "
  assert_output_contains "                       Profile:  standard                     "
  assert_output_contains "                    Theme name:  resisting                    "
  assert_output_contains "                           URL:  www.resistance-star-wars.com "
  assert_output_contains "                      Web root:  web                          "
  assert_output_contains "          Install from profile:  No                           "
  assert_output_contains "      Database download source:  curl                         "
  assert_output_contains "           Database store type:  file                         "
  assert_output_contains "    Override existing database:  No                           "
  assert_output_contains "                    Deployment:  artifact                     "
  assert_output_contains "               FTP integration:  Disabled                     "
  assert_output_contains "            Acquia integration:  Enabled                      "
  assert_output_contains "            Lagoon integration:  Enabled                      "
  assert_output_contains "       RenovateBot integration:  Enabled                      "
  assert_output_contains "     Preserve docs in comments:  Yes                          "
  assert_output_contains "      Preserve Vortex comments:  Yes                          "
}

@test "Install parameters: pre-installed; overrides, normalisation and discovery; interactive; user input overrides discovery which overrides defaults" {
  export VORTEX_INSTALL_PROCEED=0

  fixture_preinstalled web

  # Order of values overrides for interactive installation into existing dir (bottom wins):
  # - default value
  # - discovered value
  # - entered value (but if nothing entered, will fall back to discovered value).
  answers=(
    "star Wars"              # name
    "star wars MaCHine"      # machine_name
    "The Empire"             # org
    "the new empire"         # morh_machine_name
    "W s"                    # module_prefix
    "S w Profile"            # profile
    "light saber"            # theme
    "resistance forever.com" # URL
    "nothing"                # webroot
    "nah"                    # provision_use_profile
    "image"                  # database_download_source
    "image"                  # database_store_type
    "no"                     # override_existing_db
    "no"                     # deploy_type
    "Y"                      # preserve_ftp
    "nothing"                # preserve_acquia - testing NOTHING value - should be 'Enabled' as exists in fixture.
    "nah"                    # preserve_lagoon
    "nothing"                # preserve_renovatebot - testing NOTHING value - should be 'Enabled as exists in fixture.
    "n"                      # preserve_doc_comments
    "n"                      # preserve_vortex_info
  )

  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_output_contains "It looks like Vortex is already installed into this project."
  assert_output_contains "Aborting project installation. No files were changed"

  # Note that user input takes precedence over discovered values.
  assert_output_contains "                          Name:  Star wars              "
  assert_output_contains "                  Machine name:  star_wars_machine      "
  assert_output_contains "                  Organisation:  The Empire             "
  assert_output_contains "     Organisation machine name:  the_new_empire         "
  assert_output_contains "                 Module prefix:  w_s                    "
  assert_output_contains "                       Profile:  s_w_profile            "
  assert_output_contains "                    Theme name:  light_saber            "
  assert_output_contains "                           URL:  resistance-forever.com "
  assert_output_contains "                      Web root:  web                    "
  assert_output_contains "          Install from profile:  No                     "
  assert_output_contains "      Database download source:  container_registry     "
  assert_output_contains "           Database store type:  container_image        "
  assert_output_contains "    Override existing database:  No                     "
  assert_output_contains "                    Deployment:  Disabled               "
  assert_output_contains "               FTP integration:  Enabled                "
  assert_output_contains "            Acquia integration:  Enabled                "
  assert_output_contains "            Lagoon integration:  Disabled               "
  assert_output_contains "       RenovateBot integration:  Enabled                "
  assert_output_contains "     Preserve docs in comments:  No                     "
  assert_output_contains "      Preserve Vortex comments:  No                     "
}

#
#
# Helper to create fixture files to fake pre-installed state.
#
# Note that this helper provides only one state of the fixture site.
#
fixture_preinstalled() {
  local webroot="${1:-web}"

  # Create readme file to pretend that Vortex was installed.
  create_fixture_readme

  # Sets 'name' to 'Resistance new site'.
  # Sets 'machine_name' to 'resistance_site'.
  # Sets 'org' to 'The Resistance'.
  # Sets 'org_machine_name' to 'the_next_resistance'.
  create_fixture_composerjson "Resistance new site" "resistance_site" "The Resistance" "the_next_resistance"

  # Sets 'module_prefix' to 'another_resist'.
  mkdir -p "${webroot}/modules/custom/another_resist_core"
  mkdir -p "${webroot}/modules/custom/some_resist_notcore"
  mkdir -p "${webroot}/modules/custom/yetanother_resist"

  # Sets 'theme' to 'resisting'.
  mktouch "${webroot}/sites/all/themes/custom/resisting/resisting.info.yml"
  mktouch "${webroot}/sites/all/themes/custom/yetanothertheme/yetanothertheme.info.yml"

  # Sets 'url' to 'www.resistance-star-wars.com'.
  mkdir -p "${webroot}/sites/default"
  echo "  \$config['stage_file_proxy.settings']['origin'] = 'http://www.resistance-star-wars.com/';" >"${webroot}/sites/default/settings.php"

  # Sets 'preserve_acquia' to 'Yes'.
  mkdir -p hooks
  # Sets 'preserve_lagoon' to 'Yes'.
  touch .lagoon.yml
  # Sets 'preserve_dependencies' to 'Yes'.
  touch renovate.json

  echo "VORTEX_WEBROOT=${webroot}" >>.env

  # Sets 'fresh_install' to 'No'.
  echo "VORTEX_PROVISION_USE_PROFILE=0" >>.env

  # Sets 'override_existing_db' to 'No'.
  echo "VORTEX_PROVISION_OVERRIDE_DB=0" >>.env

  # Sets 'preserve_doc_comments' to 'Yes'.
  echo "# Ahoy configuration file." >>.ahoy.yml

  # Sets 'preserve_vortex_info' to 'Yes'.
  echo "# Comments starting with '#:' provide explicit documentation and will be" >>.ahoy.yml
}
